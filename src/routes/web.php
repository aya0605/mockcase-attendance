<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\User\ApplicationController as UserApplicationController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\AdminController; 
use App\Http\Controllers\Admin\UserController;




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

// 一般ユーザー向けの認証済みルート
Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::post('/attendance/start-work', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end-work', [AttendanceController::class, 'endWork']);
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak']);
    Route::get('/attendance/list', [AttendanceController::class,'list']);
    Route::get('/attendance/detail/{attendanceId}', [AttendanceController::class, 'detail']);
    Route::post('/attendance/update-application/{attendanceId}', [AttendanceController::class, 'submitApplication']);
    
    Route::get('/applications', [UserApplicationController::class, 'index']);
    Route::get('/user/applications/{applicationId}', [UserApplicationController::class, 'show']);

});

// 管理者専用の認証済みルート
Route::middleware(['auth', 'can:admin-access'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    
    // 勤怠関連のルートを上にまとめる
    Route::get('/attendances/{attendanceId}', [AdminController::class, 'detail']);
    Route::post('/attendances/{attendanceId}', [AdminController::class, 'update']);
    Route::put('/attendances/{attendance}', [AdminController::class, 'update']);

    // ユーザー関連のルートをより具体的なものから順にまとめる
    Route::get('/users/{user}/attendances/{year}/{month}', [UserController::class, 'showMonthlyAttendance']);
    Route::get('/users/{user}/attendances/{attendanceId}', [UserController::class, 'showAttendanceDetail']);
    Route::get('/users/{user}/attendances', [UserController::class, 'showAttendanceList']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::get('/users', [UserController::class, 'index']);

    // アプリケーション関連のルート
    Route::get('/applications', [AdminApplicationController::class, 'index']);
    Route::get('/applications/{applicationId}', [AdminApplicationController::class, 'show']);
    Route::post('/applications/{application}/approve', [AdminApplicationController::class, 'approve']); 
    Route::post('/applications/{application}/reject', [AdminApplicationController::class, 'reject']); 
});