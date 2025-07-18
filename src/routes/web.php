<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

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
    Route::get('/', [AttendanceController::class, 'index']);
    Route::post('/attendance/start-work', [AttendanceController::class, 'startWork']);
    Route::post('/attendance/end-work', [AttendanceController::class, 'endWork']);
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak']);
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak']);

    Route::get('/attendance/list', [AttendanceController::class,'list']);
});
