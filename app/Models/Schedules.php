<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Schedules extends Model
{
    use HasFactory;

    public static function getSchedule(mixed $employe, int $dayOfWeek)
    {
        $query = DB::table('schedules as a')
            ->select('a.*','b.special_schedule')
            ->join('employee_schedules as b', 'a.schedule_id', '=', 'b.schedule_id')
            ->join('employees as c', 'b.role_id', '=', 'c.role')
            ->where('day_id', ($dayOfWeek))
            ->where('c.id', $employe->id);
            return $query->first();
    }

    public function eSchedule()
    {
        return $this->belongsTo(EmployeeSchedule::class,'schedule_id','schedule_id');
    }
}
