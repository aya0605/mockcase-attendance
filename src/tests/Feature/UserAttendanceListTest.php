<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserAttendanceListTest extends TestCase
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
    private function createAttendance($user, $date, $startTime, $endTime)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            // CarbonでJSTを明示して作成 (テスト実行時のタイムゾーンに依存しないように)
            'start_time' => Carbon::parse("{$date} {$startTime}", 'Asia/Tokyo'),
            'end_time' => Carbon::parse("{$date} {$endTime}", 'Asia/Tokyo'),
        ]);
    }

    /** @test */
    public function ログインした一般ユーザーは自分の勤怠一覧を正しく閲覧できる()
    {
        // 1. 準備 (Arrange)
        // ログインする一般ユーザー (role=0またはnull)
        $userA = User::factory()->create(['name' => 'テスト太郎', 'role' => 0]);
        // 他のユーザー
        $userB = User::factory()->create(['name' => 'テスト花子', 'role' => 0]); 

        // ユーザーAの勤怠 (表示されるべきデータ)
        $dateA = '2025-10-15';
        $attendanceA = $this->createAttendance($userA, $dateA, '09:00:00', '18:00:00');

        // ユーザーBの勤怠 (表示されないべきデータ)
        $dateB = '2025-10-16';
        $attendanceB = $this->createAttendance($userB, $dateB, '10:00:00', '19:00:00');

        // 2. 実行 (Act)
        // ユーザーAとして勤怠一覧ページにアクセス
        $response = $this->actingAs($userA)->get('/attendance/list');

        // 3. 検証 (Assert)
        $response->assertStatus(200); // 正常にページが表示されること

        // ユーザーAのデータが表示に含まれていること (日付で確認)
        $response->assertSee($dateA);
        
        // ユーザーBのデータが表示に含まれていないこと
        $response->assertDontSee($dateB);
        $response->assertDontSee('テスト花子');
    }
}
