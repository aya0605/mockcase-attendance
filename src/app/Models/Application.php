<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'applied_start_time',
        'applied_end_time',
        'applied_breaks',
        'note',
        'status',
    ];

    protected $casts = [
        'applied_breaks' => 'array', // JSONカラムを自動で配列にキャスト
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
