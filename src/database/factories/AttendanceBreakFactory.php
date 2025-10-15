<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;

class AttendanceBreakFactory extends Factory
{
   protected $model = AttendanceBreak::class;

    public function definition()
    {
       $date = Carbon::now()->toDateString();
        
        return [
            'attendance_id' => Attendance::factory(),
            'start_time' => Carbon::parse($date . ' 12:00:00'),
            'end_time' => Carbon::parse($date . ' 13:00:00'),
        ];
    }
}
