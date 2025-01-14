<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class AccountingGroup
{
    public $productTitle = "";
    public $groupName = "";
    public $taxRate = "";

    function __construct() {
    }

    public static function loadCsvRow($row) {
        $result = new AccountingGroup();
        $result->productTitle = $row[0];
        $result->groupName = $row[1] != "fuu" ? $row[1] : "fuu.";
        $result->taxRate = $row[2];
        return $result;
    }

    public static function get() {
        $csvFile = ".".Storage::url('app/csv/products_accounting_group_0113.csv');

        $accountingGroups = [];
        $accountingGroupProductTitles = [];

        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $group = AccountingGroup::loadCsvRow($csvRow);
            if(in_array($group->productTitle, $accountingGroupProductTitles)) continue;
            $accountingGroups[] = $group;
            $accountingGroupProductTitles[] = $group->productTitle;
            $line .= $group->toString()."\n";
        }
        fclose($file_handle);

        return $accountingGroups;
    }

    public static function getDuplicateCode($csvFile, $targetColumnName = "title") {
        $count = 1;
        $delimiter = ",";
        $line = "";
        $file_handle = fopen($csvFile, 'r');
        $targetColumnValues = [];
        $duplicateTargetColumnValues = [];
        while ($csvRow = fgetcsv($file_handle, null, $delimiter)) {
            $group = AccountingGroup::loadCsvRow($csvRow);
            $targetColumnValue = $targetColumnName == "title" ? $group->productTitle : "";
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

    function getGroupCode() {
        if($this->groupName == "パティスリー") return "パティスリー";
        return $this->groupName.$this->taxRate."%";
    }

    function equals($title) {
        $thisTitle = $this->strtolower(trim($this->productTitle));
        $otherTitle = $this->strtolower(trim($title));
        return $thisTitle == $otherTitle;
    }

    function strtolower($input) {
        $input = \str_replace(["　", " "], ["", ""], $input);

        // 全角英数字を半角英数字に置き換える
        $input = mb_convert_kana($input, 'a', 'UTF-8');

        // 半角カタカナを全角カタカナに置き換える
        $input = mb_convert_kana($input, 'K', 'UTF-8');

        // 大文字英数字を小文字に変換
        return preg_replace_callback('/[A-Z0-9]|　/u', function ($matches) {
            return strtolower($matches[0]);
        }, $input);
    }

    function toString() {
        return $this->productTitle.",".$this->groupName.",".$this->taxRate.",".$this->getGroupCode();
    }

    function toSmaregiFormart() {
        return $this->getGroupCode();
    }
}
