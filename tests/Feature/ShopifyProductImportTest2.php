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
 * - sail artisan make:test ShopifyProductImportTest2
 * execute command
 * - sail phpunit tests/Feature/ShopifyProductImportTest2.php
 */
class ShopifyProductImportTest2 extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");
        // $csvFile = ".".Storage::url('app/csv/products_export_variation_test_1.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複した商品コードの取得
        $duplicateProductCodes = $this->get_duplicate_code($csvFile, "ProductCode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $parentProduct = null;
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;

            // バリエーション商品だった場合は、親商品から情報を引き継ぐ。
            if($parentProduct != null && $product->handle != $parentProduct->handle) $parentProduct = null;
            if($product->isParent()) $parentProduct = $product;
            if($product->isEmpty2()) continue;
            if($product->isVariation() && $parentProduct != null){
                if($parentProduct->isEmpty2()) continue;
                $product->setParentRow($parentProduct);
            }

            // スマレジに登録できる商品なのかチェック
            if(!$product->isValidImportSmaregi($duplicateProductCodes, $exclutionTitles, true)) continue;

            $ifnullProductCode = sprintf('UNIMA%08d', 1000000 + $count);
            $line .= $product->toSmaregiFormart2(1000000 + $count, $ifnullProductCode).",".$product->getGroupName($groups)."\n";
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
        // $csvFile = ".".Storage::url('app/csv/products_export_variation_test_1.csv');
        $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0105.csv');

        // 重複したコードの取得
        $duplicateProductCodes = $this->get_duplicate_code($csvFile, "ProductCode");
        $duplicateSkus = $this->get_duplicate_code($csvFile, "Sku");
        $duplicateBarcodes = $this->get_duplicate_code($csvFile, "Barcode");

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $line2 = "";
        $file_handle = fopen($csvFile, 'r');

        $parentProduct = null;
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;
            if($parentProduct != null && $product->handle != $parentProduct->handle) $parentProduct = null;
            $method = "";
            if($product->isParent()) {
                $parentProduct = $product;
                $method = "親\n";
            }
            if($product->isEmpty2()) {
                $line2 .= "除\n";
                continue;
            }
            if($product->isVariation() && $parentProduct != null){
                if($parentProduct->isEmpty2()) {
                    $line2 .= "親除\n";
                    continue;
                }
                $product->setParentRow($parentProduct);
                $method = "バ\n";
            }
            
            $line2 .= $method;

            $message = "";
            if(in_array($product->sku, $duplicateSkus)) $message .= "×SKUが重複している";
            if(in_array($product->barcode, $duplicateBarcodes)) $message .= "×バーコードが重複している";
            if(in_array($product->getProductCode(), $duplicateProductCodes)) $message .= "×商品コードが重複している";
            if(strlen($product->getProductCode()) > 20) $message .= "×商品コードが20文字より大きい。";
            if($product->title == "") $message .= "×商品名がない";

            $groupName = $product->getGroupName($groups);
            if($groupName == "") $message .= "×経理用分類が存在しない";

            $line .= $product->toString().",".$message."\n";
        }
        // \Log::debug($line);
        \Log::debug($line2);
        fclose($file_handle);
    }

    /**
     * A basic feature test example.
     */
    private function get_duplicate_code($csvFile, $targetColumnName = "ProductCode"): array
    {
        \Log::debug("start: test_duplicate_product_code");

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $targetColumnValues = [];
        $duplicateTargetColumnValues = [];
        $parentProduct = null;
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            if($parentProduct != null && $product->handle != $parentProduct->handle) $parentProduct = null;
            if($product->isParent()) $parentProduct = $product;
            if($product->isEmpty2()) continue;
            if($product->isVariation() && $parentProduct != null){
                if($parentProduct->isEmpty2()) continue;
                $product->setParentRow($parentProduct);
            }
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
