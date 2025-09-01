<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class earningLogsModel extends Model
{
    use HasFactory;

    protected $table = "earning_logs";

    public $timestamps = false;
}
