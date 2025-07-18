<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'work_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class); 
    }
}
