<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Admin\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    // 勤怠関連のルート
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/attendance/index', [AttendanceController::class, 'index']);
    Route::post('/attendance/start-work', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end-work', [AttendanceController::class, 'endWork']);
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak']);
    Route::get('/attendance/list', [AttendanceController::class,'list']);
    Route::get('/attendance/detail/{attendanceId}', [AttendanceController::class, 'detail']);
    Route::post('/attendance/update-application/{attendanceId}', [AttendanceController::class, 'submitApplication']);

    // 申請関連のルート
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve']);
    Route::post('/applications/{application}/reject', [ApplicationController::class, 'reject']);


    // 管理者専用のルート（ここから追加）
    Route::middleware(['can:admin-access'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        // 他の管理者専用ルートもここに追加
    });
});
