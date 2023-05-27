<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    static public function getCheckIn($tlg_id)
    {
        return self::where('tlg_id', $tlg_id)->where('check_out', null)->first();
    }

    static public function getCurrentDate($tlg_id,$date)
    {
        return self::where('tlg_id', $tlg_id)->whereDate('check_in', $date)->first();
    }
}
