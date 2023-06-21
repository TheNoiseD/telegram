<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShceduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $days = array(
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7,
        );
        foreach ($days as $key => $value) {
            \App\Models\Schedules::create([
                'schedule_id' => 2,
                'day_id' => $value,
                'start' => '07:00:00',
                'end' => '16:00:00',
            ]);
        }
    }
}
