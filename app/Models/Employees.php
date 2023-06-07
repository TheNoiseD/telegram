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
}
