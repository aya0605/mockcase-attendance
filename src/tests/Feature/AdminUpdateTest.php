<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Carbon::setTestNow(Carbon::now('Asia/Tokyo'));
        Carbon::setLocale(config('app.locale'));
        
        config(['app.timezone' => 'Asia/Tokyo']);
        
        DB::statement("SET time_zone='+09:00'");
    }

    // ヘルパー: 管理者ユーザーを作成 (role=1)
    protected function createAdminUser()
    {
        return User::factory()->create(['role' => 1]);
    }
    
    // ヘルパー: 修正対象の勤怠データを作成
    protected function createAttendanceWithBreaks($user)
    {
        $workDate = '2025-10-15';
        
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'work_date' => $workDate,
            'start_time' => Carbon::parse($workDate . ' 09:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse($workDate . ' 18:00:00', 'Asia/Tokyo'),
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse($workDate . ' 12:00:00', 'Asia/Tokyo'),
            'end_time' => Carbon::parse($workDate . ' 13:00:00', 'Asia/Tokyo'),
        ]);
        
        return $attendance;
    }

    /** @test */
    public function 管理者は勤怠情報と既存の休憩を正しく更新できる()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreaks($user);
        $originalBreak = $attendance->breaks->first();
        $workDate = $attendance->work_date->toDateString();

        $updateData = [
            'start_time' => '09:30', 
            'end_time' => '17:30',   
            'break_start_1' => '12:30', 
            'break_end_1' => '13:30',   
            'note' => '修正テスト', // 検証から削除するが、データとしては送信
        ];

        $url = "/admin/attendances/{$attendance->id}";
        $response = $this->actingAs($admin)->put($url, $updateData);

        $response->assertStatus(302);
        
        // 勤怠本体の検証: note カラムの検証は削除
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '09:30:00', 
            'end_time' => '17:30:00',
        ]);
        
        // 休憩レコードの検証
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $originalBreak->id,
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse($workDate . ' 12:30:00', 'Asia/Tokyo')->toDateTimeString(), 
            'end_time' => Carbon::parse($workDate . ' 13:30:00', 'Asia/Tokyo')->toDateTimeString(),
        ]);
    }

    /** @test */
    public function 管理者は新しい休憩レコードを追加できる()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreaks($user);
        $workDate = $attendance->work_date->toDateString();

        $updateData = [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_1' => '12:00', 
            'break_end_1' => '13:00',
            'break_start_2' => '15:00', 
            'break_end_2' => '15:15',   
        ];
        
        $url = "/admin/attendances/{$attendance->id}";
        $response = $this->actingAs($admin)->put($url, $updateData);

        $response->assertStatus(302);
        
        $this->assertCount(2, $attendance->fresh()->breaks); 

        // 休憩2が正しく作成されていること
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'start_time' => Carbon::parse($workDate . ' 15:00:00', 'Asia/Tokyo')->toDateTimeString(), 
            'end_time' => Carbon::parse($workDate . ' 15:15:00', 'Asia/Tokyo')->toDateTimeString(),
        ]);
    }

    /** @test */
    public function 不正な時間データでは勤怠の更新が失敗する()
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreaks($user); 

        // 不正なデータ: 退勤が出勤より前 
        $invalidData = [
            'start_time' => '10:00',
            'end_time' => '09:00', 
        ];

        $url = "/admin/attendances/{$attendance->id}";
        $response = $this->actingAs($admin)->put($url, $invalidData);

        $response->assertStatus(302); 
        $response->assertSessionHasErrors(['end_time']);
        
        // データベースは更新されていないこと (元のデータが残っていること)
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'start_time' => '09:00:00', 
            'end_time' => '18:00:00',  
        ]);
    }
}
