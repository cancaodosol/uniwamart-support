<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\AccountingGroup;

class EccubeClassCategory
{
    public $id = "";
    public $class_name_id = "";
    public $backed_name = "";
    public $name = "";

    function __construct() {
    }

    public static function loadCsvRow($row) {
        $result = new EccubeClassCategory();
        $result->id = $row[0];
        $result->class_name_id = $row[1];
        $result->backed_name = $row[3];
        $result->name = $row[4];
        return $result;
    }

    public function getClassNameId() {
        return $this->class_name_id + 136;
    }

    public function toString() {
        return implode(",", [
            $this->getClassNameId(),
            "",
            $this->backed_name,
            $this->name,
            "",
        ]);
    }
}