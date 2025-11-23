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
 * - sail artisan make:test ShopifyProductImportTest2Eccube
 * execute command
 * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_example
 */
class ShopifyProductImportTest2Eccube extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");
        // $csvFile = ".".Storage::url('app/csv/products_export_variation_test_1.csv');
        // $csvFile = ".".Storage::url('app/csv/products_export_1_AND_2_0113_hand.csv');
        $csvFile = ".".Storage::url('app/csv/smaregi/products_export_1 2.csv');
        $duplicateProductCodes = [];

        // 経理用の分類を取得
        $groups = AccountingGroup::get();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $parentProduct = null;

        $productIds = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $count++;

            // if($count > 500) break;

            // バリエーション商品だった場合は、親商品から情報を引き継ぐ。
            if($parentProduct != null && $product->handle != $parentProduct->handle) $parentProduct = null;
            if($product->isParent()) $parentProduct = $product;
            if($product->isEmpty2()) continue;
            if($product->isVariation() && $parentProduct != null){
                if($parentProduct->isEmpty2()) continue;
                $product->setParentRow($parentProduct);
            }

            // スマレジに登録できる商品なのかチェック
            if(!$product->isValidImportSmaregi($duplicateProductCodes, false)) continue;
            if($product->isVariation()) continue;

            $line .= $product->toEccubeFormat(1000000 + $count, [])."\n";
        }
        \Log::debug($line);
        fclose($file_handle);
    }

    /**
     * Shopify商品データから、バリエーションを抽出し、ECCUBEの商品規格フォーマットで出力する。
     * 規格としての登録、規格分類としての登録の２段階が必要。
     * 
     * execute command
     * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_export_cecube_classes
     */
    public function test_export_cecube_classes(): void
    {
        \Log::debug("start: test_export_cecube_classes");
        $csvFile = ".".Storage::url('app/csv/smaregi/products_export_1 2.csv');
        $variationProduts2 = $this->get_variation_products();

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $parentProduct = null;

        $variationProduts = [];

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

            // シロフク商品は移行しない
            if(str_contains($product->getTitle(), "コーヒー") || str_contains($product->getTitle(), "シロフク")) continue;

            // バリエーション商品（規格分類あり商品）の抽出
            if($product->isVariation()){
                $variationProduts[] = $product;
            }
        }

        // バリエーション商品（規格分類あり商品）の書き出し
        $productLine = "";
        foreach($variationProduts as $product){
            $productLine .= $product->toEccubeFormat(1000000 + $count, $variationProduts2)."\n";  
        }

        // \Log::debug($line);
        \Log::debug($productLine);

        // // 商品規格名と、規格分類を抽出
        $productClasses1 = [];
        $productClasses2 = [];
        foreach($variationProduts as $product){
            if($product->optionName1 != null && !array_key_exists($product->optionName1, $productClasses1)){
                $productClasses1[$product->optionName1] = [];
            }
            if($product->optionName1 != null && !in_array($product->optionValue1, $productClasses1[$product->optionName1])){
                $productClasses1[$product->optionName1][] = $product->optionValue1;
            }
            if($product->optionName2 != null && !array_key_exists($product->optionName2, $productClasses2)){
                $productClasses2[$product->optionName2] = [];
            }
            if($product->optionName2 != null && !in_array($product->optionValue2, $productClasses2[$product->optionName2])){
                $productClasses2[$product->optionName2][] = $product->optionValue2;
            }  
        }

        $classesId = 7; // 商品規格IDの開始番号（ECCubeに登録されている規格IDを確認して、セット。）
        $classesLine = "";
        $classesCategoryLine = "";
        foreach($productClasses1 as $className => $classValues){
            $classesLine .= implode(",", [
                "",
                $className,
                $className,
                "0",
            ])."\n";
            foreach($classValues as $classValue){
                $classesCategoryLine .= implode(",", [
                    $classesId,
                    $className,
                    "",
                    $classValue,
                    $classValue,
                    "0",
                ])."\n";
            }
            $classesId++;
        }
        foreach($productClasses2 as $className => $classValues){
            $classesLine .= implode(",", [
                "",
                $className,
                $className,
                "0",
            ])."\n";
            foreach($classValues as $classValue){
                $classesCategoryLine .= implode(",", [
                    $classesId,
                    $className,
                    "",
                    $classValue,
                    $classValue,
                    "0",
                ])."\n";
            }
            $classesId++;
        }
        \Log::debug($classesLine);
        \Log::debug($classesCategoryLine);

        fclose($file_handle);
    }

}
