<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Employees extends Model
{
    use HasFactory;

    static public function getEmploye(mixed $id)
    {
        return self::where('tlg_id', $id)->first();
    }

    public function getEmployeByUser(string $substr)
    {
        return self::where('username', 'like', '%' . $substr . '%')->first();
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class,'tlg_id','tlg_id');
    }
    public function breakTime()
    {
        return $this->hasMany(BreakTime::class,'tlg_id','tlg_id');
    }
    public function absencesTime()
    {
        return $this->hasMany(AbsencesTime::class,'employe_id','tlg_id');
    }

    public function justifyAbsences()
    {
        return $this->hasMany(JustifyAbsences::class,'tlg_id','tlg_id');
    }

    public function salary()
    {
        return $this->hasOne(Salary::class,'employe_id','id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class,'role','id');
    }

    public function absence()
    {
        return $this->hasMany(Absences::class,'tlg_id','tlg_id');
    }

}
