<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JustifyAbsences extends Model
{
    use HasFactory;

    public function getAbsenceToDay(Employees $tlg_id)
    {
        return self::where('tlg_id', $tlg_id->tlg_id)->whereDate('date', now()->format('Y-m-d'))->first();
    }
}
