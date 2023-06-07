<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsencesTime extends Model
{
    use HasFactory;

    public function create(Employees $employe, string $diff, string $type, Carbon $date):void
    {
        $this->employe_id = $employe->id;
        $this->time = $diff;
        $this->type = $type;
        $this->date = $date;
        $this->save();
    }
}
