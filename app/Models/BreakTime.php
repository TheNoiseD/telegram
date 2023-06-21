<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    static public function getBreak(mixed $tlg_id)
    {
        return self::where('tlg_id',$tlg_id)->whereDate('break_in',now('America/New_York'))->where('break_out',null)->first();
    }

    public function employee()
    {
        return $this->belongsTo(Employees::class,'tlg_id','tlg_id');
    }
}
