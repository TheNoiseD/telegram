<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absences extends Model
{
    use HasFactory;

    public function employee()
    {
        return $this->belongsTo(Employees::class,'tlg_id','tlg_id');
    }
}
