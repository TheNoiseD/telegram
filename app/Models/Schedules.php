<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Schedules extends Model
{
    use HasFactory;

    public static function getSchedule(mixed $employe, int $dayOfWeek): object
    {
        $query = DB::table('schedules as a')
            ->select('a.*')
            ->join('employe_schedule as b', 'a.schedule_id', '=', 'b.schedule_id')
            ->join('employees as c', 'b.role_id', '=', 'c.role')
            ->where('day_id', ($dayOfWeek + 1))
            ->where('c.id', $employe->id);
            return $query->first();
    }
}
