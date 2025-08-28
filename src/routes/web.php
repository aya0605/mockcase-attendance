<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\User\ApplicationController as UserApplicationController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
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

// 一般ユーザーと管理者の共通ルート
Route::middleware('auth')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/start-work', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end-work', [AttendanceController::class, 'endWork']);
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak']);
    Route::get('/attendance/list', [AttendanceController::class,'list']);
    Route::get('/attendance/detail/{attendanceId}', [AttendanceController::class, 'detail']);
    Route::post('/attendance/update-application/{attendanceId}', [AttendanceController::class, 'submitApplication']);
    
    // AdminControllerのログインリダイレクト
    Route::get('/login/redirect', [AdminController::class, 'handleLoginRedirect']);
    
    // 一般ユーザー用の申請ルート
    // `ApplicationController` を `UserApplicationController` に変更
    Route::get('/applications', [UserApplicationController::class, 'index']);
});


// 管理者専用の認証済みルート
Route::middleware(['auth', 'can:admin-access'])->prefix('admin')->group(function () {
    // 勤怠一覧をダッシュボードとして表示する新しいルート
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    
    // ユーザーごとの勤怠一覧を表示するルート
    Route::get('/users/{id}/attendances', [AdminController::class, 'usersMonthlyAttendances']);
    Route::get('/users', [AdminController::class, 'usersIndex']);

    // 勤怠詳細
    Route::get('/attendance/detail/{attendanceId}', [AdminController::class, 'detail']);
    
    // 既存の勤怠詳細ルートは削除またはコメントアウト
    // Route::get('/attendances/{attendanceId}', [AdminController::class, 'detail']);
    Route::post('/attendances/{attendanceId}', [AdminController::class, 'update']);
    
    // 申請一覧関連のルート
    Route::get('/applications', [AdminApplicationController::class, 'index']);
    Route::get('/applications/{applicationId}', [AdminApplicationController::class, 'show']);

    // 承認・却下処理のためのルートをここに統合
    Route::post('/applications/{application}/approve', [AdminApplicationController::class, 'approve']); 
    Route::post('/applications/{application}/reject', [AdminApplicationController::class, 'reject']); 
});

