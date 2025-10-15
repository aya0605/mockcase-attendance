<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
       $date = $this->faker->date();
        $startTime = Carbon::parse($date . ' 09:00:00');
        $endTime = Carbon::parse($date . ' 18:00:00');

        return [
            'user_id' => User::factory(), 
            'work_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
