<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Support\Carbon;


class UserBreakTest extends TestCase
{
    use RefreshDatabase;

    // テスト実行前のセットアップ
    protected function setUp(): void
    {
        parent::setUp();
        // テスト中のCarbonのタイムゾーンをJSTに設定
        Carbon::setTestNow(Carbon::now('Asia/Tokyo'));
    }

    // ヘルパー: 勤怠データを作成し、時間を固定する
    private function createAttendance($user, $date, $startTime, $endTime = null)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            // CarbonでJSTを明示して作成
            'start_time' => Carbon::parse("{$date} {$startTime}", 'Asia/Tokyo'),
            'end_time' => $endTime ? Carbon::parse("{$date} {$endTime}", 'Asia/Tokyo') : null,
        ]);
    }

    /** @test */
    public function 勤務中のユーザーは休憩を開始できる()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        $startTime = '09:00:00';
        
        // ユーザーを「勤務開始済み」の状態にする
        $attendance = $this->createAttendance($user, $date, $startTime);

        // 打刻時刻を固定 (休憩開始時刻)
        $breakStartTime = Carbon::parse("{$date} 12:00:00", 'Asia/Tokyo');
        Carbon::setTestNow($breakStartTime);

        // 2. 実行 (Act)
        $response = $this->actingAs($user)->post('/attendance/start-break');

        // 3. 検証 (Assert)
        $response->assertStatus(302) // リダイレクトを確認
                 ->assertRedirect('/');

        // 休憩レコードが正しく作成され、開始時刻が記録されていること
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'start_time' => $breakStartTime->toDateTimeString(), // DB形式に変換
            'end_time' => null, // 終了時刻がnullであること
        ]);
    }

    /** @test */
    public function 休憩中のユーザーは休憩を終了できる()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // ユーザーを「休憩開始済み」の状態にする: 勤怠開始 -> 休憩開始
        $attendance = $this->createAttendance($user, $date, '09:00:00');
        $breakStartTime = Carbon::parse("{$date} 12:00:00", 'Asia/Tokyo');
        
        // 既存の休憩レコードを作成（終了時刻はnull）
        $existingBreak = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => $breakStartTime,
            'end_time' => null,
        ]);

        // 打刻時刻を固定 (休憩終了時刻)
        $breakEndTime = Carbon::parse("{$date} 13:00:00", 'Asia/Tokyo');
        Carbon::setTestNow($breakEndTime);

        // 2. 実行 (Act)
        $response = $this->actingAs($user)->post('/attendance/end-break');

        // 3. 検証 (Assert)
        $response->assertStatus(302)
                 ->assertRedirect('/');

        // 休憩レコードが正しく更新され、終了時刻が記録されていること
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $existingBreak->id,
            'attendance_id' => $attendance->id,
            'end_time' => $breakEndTime->toDateTimeString(), // DB形式に変換
        ]);
    }
    
    /** @test */
    public function 休憩中のユーザーは二重に休憩を開始できない()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // ユーザーを「休憩開始済み」の状態にする
        $attendance = $this->createAttendance($user, $date, '09:00:00');
        $breakStartTime = Carbon::parse("{$date} 12:00:00", 'Asia/Tokyo');
        
        // 既存の休憩レコードを作成（終了時刻はnull）
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => $breakStartTime,
            'end_time' => null,
        ]);

        // 2. 実行 (Act)
        // 再度休憩開始を試みる
        $response = $this->actingAs($user)->post('/attendance/start-break');

        // 3. 検証 (Assert)
        $response->assertStatus(302) 
                 ->assertRedirect('/'); 
        
        // エラーメッセージ（またはセッションデータ）があることを確認することが望ましいが、
        // 現状は二重打刻により休憩レコードが増えていないことを確認する
        $this->assertCount(1, $attendance->breaks); // レコード数は1のまま
    }
}
