<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employees;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\CarbonInterval;
use DatePeriod;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $user = Employees::find(1)->tlg_id;


        $startDate = Carbon::create(2023, 05, 31)->startOfDay();
        $endDate = Carbon::create(2023, 06, 14)->startOfDay();

        $dates = [];
        $interval = CarbonInterval::day();

        $period = new DatePeriod($startDate, $interval, $endDate->addDay());

        foreach ($period as $date) {
            $dates[] = $date;
        }

        $randomDate = $this->faker->unique()->randomElement($dates);
        $checkIn = $randomDate->copy()->setTime(rand(7,8), rand(0,59), 0);

        $maxCheckOutTime = $checkIn->copy()->setTime(14, 0, 0);
        $checkOut = $this->faker->dateTimeBetween($checkIn->copy()->setTime(13,0,0), $maxCheckOutTime);

        $checkOut = Carbon::instance($checkOut)->format('Y-m-d H:i:s');
        return [
            'tlg_id' => $user,
            'check_in' => $checkIn,
            'check_out' => $checkOut
        ];
    }
}
