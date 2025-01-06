<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use App\Models\ShopifyProduct;
use App\Models\AccountingGroup;

/** 
 * create command
 * - sail artisan make:test ShopifyProductImportTest
 * execute command
 * - sail phpunit tests/Feature/ShopifyProductImportTest.php
 */
class ShopifyProductImportTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複した商品コードの取得
        $duplicateProductCodes = $this->get_duplicate_code("ProductCode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        // スマレジに登録させたくない商品名一覧を取得
        $exclutionTitles = ShopifyProduct::getExclutionTitles();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;
            if($product->isEmpty()) continue;
            $message = "";
            if(strlen($product->getProductCode()) > 20) continue; // MEMO: 商品コードは20文字以下の制限があるため、その調査用。
            if(in_array($product->getProductCode(), $duplicateProductCodes)) continue; // MEMO: 商品コードが重複しているものは除去。
            if(in_array($product->title, $exclutionTitles)) continue; // MEMO: スマレジ取り込み対象外のため、除外。
            $ifnullProductCode = sprintf('UNIMA%08d', 1000000 + $count);
            $line .= $product->toSmaregiFormart(1000000 + $count, $ifnullProductCode).",".$product->getGroupName($groups)."\n";
        }
        \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    public function test_import_error(): void
    {
        \Log::debug("start: test_import_error");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複したコードの取得
        $duplicateHandleCodes = $this->get_duplicate_code("Handle");
        $duplicateProductCodes = $this->get_duplicate_code("ProductCode");
        $duplicateSkus = $this->get_duplicate_code("Sku");
        $duplicateBarcodes = $this->get_duplicate_code("Barcode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;
            if($product->isEmpty()) continue;
            $message = "";
            if(in_array($product->handle, $duplicateHandleCodes)) $message .= "×ハンドルが重複している";
            if(in_array($product->sku, $duplicateSkus)) $message .= "×SKUが重複している";
            if(in_array($product->barcode, $duplicateBarcodes)) $message .= "×バーコードが重複している";
            if(strlen($product->getProductCode()) > 20) $message .= "×商品コードが20文字より大きい";
            if(in_array($product->getProductCode(), $duplicateProductCodes)) $message .= "×商品コードが重複している";

            $groupName = $product->getGroupName($groups);
            if($groupName == "") $message .= "×経理用分類が存在しない";

            $line .= $product->toString().",".$groupName.",".$message."\n";
        }
        \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    private function get_duplicate_code($targetColumnName = "ProductCode"): array
    {
        \Log::debug("start: test_duplicate_product_code");
        // $csvFile = ".".Storage::url('app/csv/products_export_test.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $targetColumnValues = [];
        $duplicateTargetColumnValues = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            if($product->isEmpty()) continue;
            $targetColumnValue = $targetColumnName == "ProductCode" ? $product->getProductCode() : 
                ($targetColumnName == "Barcode" ? $product->barcode : 
                ($targetColumnName == "Sku" ? $product->sku : 
                ($targetColumnName == "Handle" ? $product->handle : "")));
            if($targetColumnValue == "") continue;
            if(in_array($targetColumnValue, $targetColumnValues)){
                if(in_array($targetColumnValue, $duplicateTargetColumnValues)) continue;
                $duplicateTargetColumnValues[] = $targetColumnValue;
                $line .= $targetColumnValue."\n";
                continue;
            }
            $targetColumnValues[] = $targetColumnValue;
        }
        fclose($file_handle);

        return $duplicateTargetColumnValues;
    }
}
