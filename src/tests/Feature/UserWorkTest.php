<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserWorkTest extends TestCase
{
    use RefreshDatabase;

    // テスト実行前のセットアップ
    protected function setUp(): void
    {
        parent::setUp();
        // テスト中のCarbonのタイムゾーンをJSTに設定
        Carbon::setTestNow(Carbon::now('Asia/Tokyo'));
    }

    // ヘルパー: 勤怠データを作成する
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
    public function 勤務外のユーザーは出勤を開始できる()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // 打刻時刻を固定 (出勤時刻)
        $workStartTime = Carbon::parse("{$date} 09:00:00", 'Asia/Tokyo');
        Carbon::setTestNow($workStartTime);

        // 2. 実行 (Act)
        $response = $this->actingAs($user)->post('/attendance/start-work');

        // 3. 検証 (Assert)
        $response->assertStatus(302) // リダイレクトを確認
                 ->assertRedirect('/');

        // 勤怠レコードが正しく作成され、開始時刻が記録されていること
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => $date,
            'start_time' => $workStartTime->format('H:i:s'), 
            'end_time' => null, 
        ]);
    }

    /** @test */
    public function 出勤中のユーザーは退勤を終了できる()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // ユーザーを「出勤開始済み」の状態にする
        $attendance = $this->createAttendance($user, $date, '09:00:00');

        // 打刻時刻を固定 (退勤時刻)
        $workEndTime = Carbon::parse("{$date} 18:00:00", 'Asia/Tokyo');
        Carbon::setTestNow($workEndTime);

        // 2. 実行 (Act)
        $response = $this->actingAs($user)->post('/attendance/end-work');

        // 3. 検証 (Assert)
        $response->assertStatus(302)
                 ->assertRedirect('/');

        // 勤怠レコードが正しく更新され、終了時刻が記録されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'end_time' => $workEndTime->format('H:i:s'), 
        ]);
    }

    /** @test */
    public function 出勤中のユーザーは二重に出勤を開始できない()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // ユーザーを「出勤開始済み」の状態にする
        $this->createAttendance($user, $date, '09:00:00');

        // 2. 実行 (Act)
        // 再度出勤を試みる
        $response = $this->actingAs($user)->post('/attendance/start-work');

        // 3. 検証 (Assert)
        $response->assertStatus(302) 
                 ->assertRedirect('/'); 
        
        // 同じ日付の勤怠レコードが1件しか存在しないことを確認 (二重打刻防止)
        $this->assertCount(1, Attendance::where('user_id', $user->id)
                                        ->where('work_date', $date)->get());
    }

    /** @test */
    public function 休憩中のユーザーは退勤を打刻できない()
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $date = '2025-10-15';
        
        // ユーザーを「休憩中」の状態にする: 勤怠開始 -> 休憩開始
        $attendance = $this->createAttendance($user, $date, '09:00:00');
        
        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse("{$date} 12:00:00", 'Asia/Tokyo'),
            'end_time' => null, // 休憩中
        ]);

        // 2. 実行 (Act)
        // 退勤を試みる
        $response = $this->actingAs($user)->post('/attendance/end-work');

        // 3. 検証 (Assert)
        $response->assertStatus(302) 
                 ->assertRedirect('/');
        
        // 退勤に失敗し、end_timeがnullのままであること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'end_time' => null,
        ]);
        
        // エラーメッセージが表示されていることを確認するのも良い (実装による)
        // $response->assertSessionHas('error');
    }
}
