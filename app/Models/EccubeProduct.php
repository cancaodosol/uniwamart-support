<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\AccountingGroup;

class EccubeProduct
{
    public $title = "";
    public $variant_barcode = "";
    public $option1_name_id = "";
    public $option1_name = "";
    public $option1_name_management = "";
    public $option1_value_id = "";
    public $option1_value = "";
    public $option2_name_id = "";
    public $option2_name = "";
    public $option2_value_id = "";
    public $option2_value = "";

    function __construct() {
    }

    public static function loadCsvRow($row) {
        $result = new EccubeProduct();

        $result->title = trim($row[0]);
        $result->variant_barcode = $row[1];
        $result->option1_name_id = (int)$row[2];
        $result->option1_name = $row[3];
        $result->option1_name_management = $row[4];
        $result->option1_value_id = $row[5];
        $result->option1_value = $row[6];
        $result->option2_name_id = $row[7];
        $result->option2_name = $row[8];
        $result->option2_value_id = $row[9];
        $result->option2_value = $row[10];

        return $result;
    }

    public function addIdIndex($index) {
        $this->option1_name_id = $this->option1_name_id + $index;
        if($this->option2_name_id) $this->option2_name_id = $this->option2_name_id + $index;
        return $this;
    }

    public function toString() {
        return implode(", ", [
            $this->variant_barcode,
            $this->option1_name_id,
            $this->option1_name,
            $this->option1_name_management,
            $this->option1_value_id,
            $this->option1_value,
            $this->option2_name_id,
            $this->option2_name,
            $this->option2_value_id,
            $this->option2_value,
        ]);
    }
}