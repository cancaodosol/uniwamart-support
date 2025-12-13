<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\AccountingGroup;

class ShopifyProduct
{
    public $rowNo = "";
    public $handle = "";
    public $title = "";
    public $type = "";
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
    public $imageSrcs = [];
    public $imagePosition = "";

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
        $result->title = mb_trim($row[1]);
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
        if($row[27]) $result->imageSrcs[] = self::transferImageFileName($row[27]);
        $result->imagePosition = $row[28];
        return $result;
    }

    function setParentRow($product){
        $this->status = $product->status;
        $this->title = mb_trim($product->title);
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

    function setRowNo(int $rowNo){
        $this->rowNo = $rowNo;
        return $this;
    }

    function addImageSrcs($imageSrcs){
        foreach($imageSrcs as $imageSrc){
            $this->imageSrcs[] = self::transferImageFileName($imageSrc);
        }
        return $this;
    }

    function clearImageSrcs(){
        $this->imageSrcs = [];
    }

    static function transferImageFileName($url){
        $path = parse_url($url, PHP_URL_PATH);
        return basename($path);
    }

    function getId(){
        return $this->handle."____".$this->imagePosition;
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

    function isValidImportEccube($duplicateProductCodes, $outputLog = false)
    {
        $message = "";
        $isVariationOnce = false;
        $onceOptions = ShopifyProduct::getVariationOnceProductTitleOptions();
        if(strlen($this->getProductCode()) > 20) $message .= "×商品コードが20文字より大きい"; // MEMO: 商品コードは20文字以下の制限があるため、その調査用。
        if(!$isVariationOnce && in_array($this->getProductCode(), $duplicateProductCodes)) $message .= "×商品コードが重複している"; // MEMO: 商品コードが重複しているものは除去。

        if(str_contains($this->getTitle(), "コーヒー") || str_contains($this->getTitle(), "シロフク")) {
            $message .= "⚪シロフク商品は取り込み対象外";
        }
        if($outputLog && $message) \Log::debug("exclute: ".$this->toString().",".$message);
        return $message == "";
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

    /**
     * ロジレス取り込み用（SKU、商品コード紐付け用）
     */
    function toLogilessFormart() {
        return $this->sku.",".$this->getProductCode();
    }

    const ECCUBE__PRODUCT_ID__NULL = '';
    const ECCUBE__PUBLISH_STATUS__PUBLISH = '1';
    const ECCUBE__SALE_TYPE__TYPE_A = '1';
    const ECCUBE__CLASS_ID__NULL = '';
    const ECCUBE__PRODUCT_CODE__NULL = ''; 

    /**
     * ECCUBE取り込み用（バリエーションなし）
     */
    function toEccubeFormat($productId, $variationProduts) {
        $classId1 = self::ECCUBE__CLASS_ID__NULL;
        foreach($variationProduts as $vp){
            if($vp->title == $this->getTitle() && $vp->option1_value == $this->optionValue1){
                $classId1 = $vp->option1_value_id;
                break;
            }
        }
        $classId2 = self::ECCUBE__CLASS_ID__NULL;
        foreach($variationProduts as $vp){
            if($vp->title == $this->getTitle() && $vp->option2_value == $this->optionValue2){
                $classId2 = $vp->option2_value_id;
                break;
            }
        }
        return implode(",", [
            self::ECCUBE__PRODUCT_ID__NULL,
            self::ECCUBE__PUBLISH_STATUS__PUBLISH,
            $this->getTitle(),
            "",
            "",
            "",
            "",
            "",
            0,
            '"'.implode(",", $this->imageSrcs).'"',
            "",
            "",
            self::ECCUBE__SALE_TYPE__TYPE_A,
            $classId1,
            $classId2,
            "",
            $this->getProductCode(),
            "",
            1,
            "",
            "",
            $this->price,
            "",
            "",
            "",
            $this->sku,
            "0",
            "",
            "",
        ]);
    }

    /**
     * ECCUBE 商品規格 取り込み用
     */
    function toEccubeClassTransferFormat() {
        return implode(",", [
            $this->rowNo,
            $this->getTitle(),
            $this->getProductCode(),
            "",
            "",
            "",
            $this->optionName1,
            $this->optionName1,
            "",
            $this->optionValue1,
            "",
            $this->optionName2,
            $this->optionValue2,
        ]);
    }
}
