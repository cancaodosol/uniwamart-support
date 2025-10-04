<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\AccountingGroup;

class ShopifyProduct
{
    public $handle = "";
    public $title = "";
    public $optionName1 = "";
    public $optionValue1 = "";
    public $optionName2 = "";
    public $optionValue2 = "";
    public $optionName3 = "";
    public $optionValue3 = "";
    public $price = "";
    public $sku = "";
    public $barcode = "";
    public $status = "";

    private $types = [
        "アクセサリー" => 1,
        "キッチン" => 2,
        "バス&トイレ" => 3,
        "バス・トイレ" => 3,
        "ファッション" => 4,
        "ブランド別" => 5,
        "ヘルス＆ビューティー" => 6,
        "リビング" => 7,
        "寝室" => 8,
        "屋外" => 9,
        "日用品" => 10,
        "書籍" => 11,
        "環境改善" => 12,
        "福袋" => 13,
        "美容・健康" => 14,
        "衣類" => 15,
        "限定公開" => 16,
        "食品" => 17,
        "食器" => 18,
        "香り" => 19,
        "giftit" => 20,
    ];

    function __construct() {
    }

    public static function getExclutionTitles() {
        return config('smaregi.exclute_product_titles');
    }

    public static function getVariationOnceProductTitleOptions() {
        return config('smaregi.variation_once_product_title_options');
    }

    public static function getNeedlessProductCodeProductTitles() {
        return config('smaregi.needless_product_code_product_titles');
    }

    public static function loadCsvRow($row) {
        $result = new ShopifyProduct();
        $result->handle = $row[0];
        $result->title = $row[1];
        $result->type = $row[5];
        $result->optionName1 = $row[8];
        $result->optionValue1 = $row[9];
        $result->optionName2 = $row[11];
        $result->optionValue2 = $row[12];
        $result->optionName3 = $row[14];
        $result->optionValue3 = $row[15];
        $result->price = $row[22];
        $result->sku = str_replace("'", "", $row[17]);
        $result->barcode = str_replace("'", "", $row[26]);
        $result->status = $row[56];
        return $result;
    }

    function setParentRow($product){
        $this->status = $product->status;
        $this->title = $product->title;
        $this->type = $product->type;
        $this->optionName1 = $product->optionName1;
        $this->optionName2 = $product->optionName2;
        $this->optionName3 = $product->optionName3;
    }

    function isEmpty(){
        if($this->title == null) return true;
        if($this->status == "draft") return true;
        return false;
    }

    function isEmpty2(){
        if($this->title == null && $this->optionValue1 == null) return true;
        if($this->status == "draft") return true;
        return false;
    }

    function isParent(){
        if($this->handle != null && $this->title != null) return true;
        return false;
    }

    function isVariation(){
        if($this->isValidOption($this->optionName1, $this->optionValue1)) return true;
        return false;
    }

    function getTypeCode(){
        return 100; // 分類は店頭で設定するため、固定値（スマレジ未分類）
        // if($this->type == null) return 99; // 未分類（スマレジの部門ID）
        // if($this->type == "Type") return "";
        // return $this->types[$this->type];
    }

    function getTitle() {
        if($this->title == "【シリウス（SIRIUS）ー響（ひびき）ー】［セット内容：スピーカー2個・インシュレーター2個・アンプ本体・スピーカーケーブル・電源ケーブル・保証書・接続方法動画＆特典音源のURL/QR記載用紙］") return "【シリウス（SIRIUS）ー響（ひびき）ー】";
        return $this->title;
    }

    function getProductCode($ifnullValue = "") {
        if(in_array($this->title, ShopifyProduct::getNeedlessProductCodeProductTitles())) return $ifnullValue;
        if($this->barcode != null) return $this->barcode;
        return $ifnullValue;
    }

    function getGroupName($groups)
    {
        foreach ($groups as $group) {
            if($group->equals($this->title)) return $group->getGroupCode();
        }
        return "";
    }

    function getOptionName()
    {
        $options = [];
        if($this->isValidOption($this->optionName1, $this->optionValue1)) $options[] = $this->optionValue1;
        if($this->isValidOption($this->optionName2, $this->optionValue2)) $options[] = $this->optionValue2;
        if($this->isValidOption($this->optionName3, $this->optionValue3)) $options[] = $this->optionValue3;
        return implode("/", $options);
    }

    function isValidOption($optionName, $optionValue)
    {
        if(!$optionName && !$optionValue) return false;
        if($optionName == "Title" && $optionValue == "Default Title") return false;
        return true;
    }

    function clearAllOptions()
    {
        $this->optionName1 = "";
        $this->optionValue1 = "";
        $this->optionName2 = "";
        $this->optionValue2 = "";
        $this->optionName3 = "";
        $this->optionValue3 = "";
    }

    function isValidImportSmaregi($duplicateProductCodes, $outputLog = false)
    {
        $message = "";
        $isVariationOnce = false;
        $onceOptions = ShopifyProduct::getVariationOnceProductTitleOptions();
        if(in_array($this->title, array_keys($onceOptions))){
            if($onceOptions[$this->title]['option_name'] == $this->getOptionName()) {
                $isVariationOnce = true;
                $this->clearAllOptions();
            } else {
                $message .= "⚪︎バリエーション単一化のため、除去";
            }
        }
        if(strlen($this->getProductCode()) > 20) $message .= "×商品コードが20文字より大きい"; // MEMO: 商品コードは20文字以下の制限があるため、その調査用。
        if(!$isVariationOnce && in_array($this->getProductCode(), $duplicateProductCodes)) $message .= "×商品コードが重複している"; // MEMO: 商品コードが重複しているものは除去。
        if(in_array($this->title, ShopifyProduct::getExclutionTitles())) $message .= "⚪︎スマレジ取り込み対象外"; // MEMO: スマレジ取り込み対象外のため、除外。
        if($message == ""){
            $groupName = $this->getGroupName(AccountingGroup::get());
            if($groupName == "") $message .= "×経理用分類が存在しない";
        }
        if($outputLog && $message) \Log::debug("exclute: ".$this->toString().",".$message);
        return $message == "";
    }

    function toString() {
        return $this->status.",".$this->type.",".$this->handle.",".$this->title.",".$this->sku.",".$this->barcode.",".$this->price.",".$this->getOptionName();
    }

    /**
     * スマレジ取り込み用（バリエーションなし）
     */
    function toSmaregiFormart($productId, $ifnullProductCode = "") {
        return $productId.",".$this->getTypeCode().",".$this->getProductCode($ifnullProductCode).",".$this->getTitle().",".$this->price.",,,,";
    }

    /**
     * スマレジ取り込み用（バリエーションあり）
     */
    function toSmaregiFormart2($productId, $ifnullProductCode = "") {
        return $productId.",".$this->getTypeCode().",".$this->getProductCode($ifnullProductCode).",".$this->getTitle().",".$this->price.",,,,,".$this->getOptionName();
    }

    /**
     * スマレジ取り込み用（SKUセット用）
     */
    function toSmaregiFormart3($productId) {
        return $productId.",".$this->sku;
    }

    function toLogilessFormart() {
        return $this->sku.",".$this->getProductCode();
    }
}
