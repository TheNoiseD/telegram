<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    public function role()
    {
        return $this->belongsTo(Role::class,'role_id','id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedules::class,'schedule_id','schedule_id');
    }
}
