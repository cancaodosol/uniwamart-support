<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use App\Models\ShopifyProduct;
use App\Models\EccubeClassCategory;
use App\Models\AccountingGroup;
use App\Models\EccubeProduct;

/** 
 * create command
 * - sail artisan make:test ShopifyProductImportTest2Eccube
 * execute command
 * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_example
 */
class ShopifyProductImportTest2Eccube extends TestCase
{
    private function get_products(): array
    {
        $csvFile = ".".Storage::url('app/csv/smaregi/products_export_1 2.csv');
        $duplicateProductCodes = [];

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $parentProduct = null;

        $products = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $product = ShopifyProduct::loadCsvRow($csvRow);
            $product->setRowNo($count);
            $count++;

            // バリエーション商品だった場合は、親商品から情報を引き継ぐ。
            if($parentProduct != null && $product->handle != $parentProduct->handle) $parentProduct = null;
            if($product->isParent()) $parentProduct = $product;
            if($product->isEmpty2()){
                if(!$product->isParent()) $parentProduct->addImageSrcs($product->imageSrcs);
                continue;
            }
            if($product->isVariation() && $parentProduct != null){
                if($parentProduct->isEmpty2()) continue;
                $product->setParentRow($parentProduct);
                $parentProduct->addImageSrcs($product->imageSrcs);
                $product->clearImageSrcs();
            }

            $products[] = $product;
        }

        fclose($file_handle);

        return $products;
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        \Log::debug("start: test_example");

        $products = $this->get_products();
        $duplicateProductCodes = [];

        $line = "";
        foreach($products as $product){
            // スマレジに登録できる商品なのかチェック
            if(!$product->isValidImportSmaregi($duplicateProductCodes, true)) continue;
            if($product->isVariation()) continue;
            $line .= $product->toEccubeFormat(1000000 + $product->rowNo, [])."\n";
        }

        \Log::debug($line);
    }

    /**
     * Shopify商品データから、バリエーション商品を抽出して一覧にする。
     * このデータを元に、step1,step2...で、バリエーション商品のインポートデータを作成する。
     * 
     * execute command
     * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_export_cecube_classes__step0
     */
    public function test_export_cecube_classes__step0(): void
    {
        \Log::debug("start: test_export_cecube_classes__step0");

        // バリエーション商品（規格分類あり商品）の抽出
        $variationProduts = [];
        $allproducts = $this->get_products();

        $line = "";
        foreach($allproducts as $product){
            if($product->isVariation() && $product->isValidImportEccube([], true)){
                $line .= $product->toEccubeClassTransferFormat()."\n";
            }
        }

        \Log::debug($line);
    }

    /**
     * Shopify商品データから、バリエーションを抽出し、ECCUBEの商品規格フォーマットで出力する。
     * 規格としての登録、規格分類としての登録の２段階が必要。
     * 
     * execute command
     * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_export_cecube_classes__step1
     */
    public function test_export_cecube_classes__step1(): void
    {
        \Log::debug("start: test_export_cecube_classes__step1");

        // バリエーション商品（規格分類あり商品）の抽出
        $variationProduts = [];
        $allproducts = $this->get_products();
        foreach($allproducts as $product){
            if($product->isVariation()){
                $variationProduts[] = $product;
            }
        }

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

        $classesId = 3; // 商品規格IDの開始番号（ECCubeに登録されている規格IDを確認して、セット。）
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
    }

    /**
     * ECCUBEに登録された規格分類テーブルデータをもとに、規格分類のインポートCSVを作成する。
     * 
     * execute command
     * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_export_cecube_classes__step2
     */
    public function test_export_cecube_classes__step2(): void
    {
        \Log::debug("start: test_export_cecube_classes__step2");
        $csvFile = ".".Storage::url('app/csv/smaregi/規格分類テーブル.csv');

        $delimiter = ",";
        $file_handle = fopen($csvFile, 'r');
        $categories = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $ca = EccubeClassCategory::loadCsvRow($csvRow);
            $categories[] = $ca;
        }
        fclose($file_handle);

        $line = "";
        foreach($categories as $category){
            $line .= $category->toString()."\n";
        }

        \Log::debug($line);
    }

    /**
     * Step1, Step2をもとに、ECCUBEに商品規格・商品規格分類を登録したあと、
     * この処理で、商品と商品規格を紐づけたインポート用CSVを作成する。
     * 
     * execute command
     * - sail phpunit tests/Feature/ShopifyProductImportTest2Eccube.php --filter test_export_cecube_classes__step3
     */
    public function test_export_cecube_classes__step3(): void
    {
        \Log::debug("start: test_export_cecube_classes__step3");
        $variationProduts2 = $this->get_variation_products();

        // バリエーション商品（規格分類あり商品）の抽出
        $line = "";
        $allproducts = $this->get_products();
        foreach($allproducts as $product){
            if(!$product->isVariation()) continue;
            // ECCUBEに登録できる商品なのかチェック
            if(!$product->isValidImportEccube([], true)) continue;
            $line .= $product->toEccubeFormat(1000000 + $product->rowNo, $variationProduts2)."\n";
        }

        \Log::debug($line);
    }

    /**
     * ECCUBEの商品規格分類情報をバインドする。
     */
    public function get_variation_products(): array
    {
        \Log::debug("start: test_bind_cecube_classes");
        $csvFile = ".".Storage::url('app/csv/smaregi/規格分類テーブル.csv');
        $pCsvFile = ".".Storage::url('app/csv/smaregi/class_category_仕分け用.csv');

        $delimiter = ",";
        $file_handle = fopen($csvFile, 'r');
        $categories = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $ca = EccubeClassCategory::loadCsvRow($csvRow);
            $categories[$ca->class_name_id][$ca->name] = $ca->id;
        }
        fclose($file_handle);

        $file_handle = fopen($pCsvFile, 'r');
        $products = [];
        $plusIdIndex = 2;
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            if($csvRow[1] == "Variant Barcode") continue;
            $p = EccubeProduct::loadCsvRow($csvRow);
            $p->addIdIndex($plusIdIndex);

            foreach($categories[$p->option1_name_id] as $name => $id){
                if($name == $p->option1_value){
                    $p->option1_value_id = $id;
                }
            }

            $products[] = $p;
        }
        fclose($file_handle);

        return $products;
    }
}
