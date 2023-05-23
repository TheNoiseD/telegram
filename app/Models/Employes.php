<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employes extends Model
{
    use HasFactory;

    public static function search(mixed $id)
    {
        return self::where('tlg_id', $id)->first();
    }
}
