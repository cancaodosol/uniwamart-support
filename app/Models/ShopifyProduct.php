<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class ShopifyProduct
{
    public $handle = "";
    public $title = "";
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

    public static function loadCsvRow($row) {
        $result = new ShopifyProduct();
        $result->handle = $row[0];
        $result->title = $row[1];
        $result->type = $row[5];
        $result->price = $row[22];
        $result->sku = $row[17];
        $result->barcode = $row[26];
        $result->status = $row[56];
        return $result;
    }

    function isEmpty(){
        if($this->title == null) return true;
        if($this->status == "draft") return true;
        return false;
    }

    function getTypeCode(){
        if($this->type == null) return 99; // 未分類（スマレジの部門ID）
        if($this->type == "Type") return "";
        return $this->types[$this->type];
    }

    function getTitle() {
        if($this->title == "【シリウス（SIRIUS）ー響（ひびき）ー】［セット内容：スピーカー2個・インシュレーター2個・アンプ本体・スピーカーケーブル・電源ケーブル・保証書・接続方法動画＆特典音源のURL/QR記載用紙］") return "【シリウス（SIRIUS）ー響（ひびき）ー】";
        return $this->title;
    }

    function getProductCode($ifnullValue = "") {
        // if($this->sku != null) return $this->sku;
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

    function toString() {
        return $this->status.",".$this->type.",".$this->handle.",".$this->title.",".$this->sku.",".$this->barcode.",".$this->getProductCode().",".$this->price;
    }

    function toSmaregiFormart($productId, $ifnullProductCode = "") {
        return $productId.",".$this->getTypeCode().",".$this->getProductCode($ifnullProductCode).",".$this->getTitle().",".$this->price.",,,,";
    }
}
