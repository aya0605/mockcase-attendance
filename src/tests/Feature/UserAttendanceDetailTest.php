<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\Attendance;
use App\Models\User;


class UserAttendanceDetailTest extends TestCase
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
            'start_time' => Carbon::parse("{$date} {$startTime}", 'Asia/Tokyo'),
            'end_time' => Carbon::parse("{$date} {$endTime}", 'Asia/Tokyo'),
        ]);
    }

    /** @test */
    public function ログインした一般ユーザーは自分の勤怠詳細を正しく閲覧できる()
    {
        // 1. 準備 (Arrange)
        // ログインする一般ユーザー
        $user = User::factory()->create(['name' => 'テスト太郎']); 

        // 自分の勤怠データ (詳細ページで確認するデータ)
        $date = '2025-10-15';
        $attendance = $this->createAttendance($user, $date, '09:00:00', '18:00:00');

        // 2. 実行 (Act)
        // 自分の勤怠詳細ページにアクセス
        $response = $this->actingAs($user)->get("/attendance/detail/{$attendance->id}");

        // 3. 検証 (Assert)
        $response->assertStatus(200); // 正常にページが表示されること

        // ページに勤怠データ（日付、出勤、退勤時刻）が含まれていることを確認
        $response->assertSee($date);
        $response->assertSee('09:00'); // 出勤時刻
        $response->assertSee('18:00'); // 退勤時刻
        $response->assertSee('テスト太郎'); // ページ上部にユーザー名が表示されることを期待
    }

    /** @test */
    public function ログインした一般ユーザーは他のユーザーの勤怠詳細にはアクセスできない()
    {
        // 1. 準備 (Arrange)
        // ログインするユーザー（アクセスを試みる側）
        $userA = User::factory()->create(); 
        // 別のユーザー（アクセスをブロックすべきデータの所有者）
        $userB = User::factory()->create(); 

        // ユーザーBの勤怠データ
        $attendanceB = $this->createAttendance($userB, '2025-10-16', '10:00:00', '19:00:00');

        // 2. 実行 (Act)
        // ユーザーAとして、ユーザーBの勤怠詳細ページにアクセスを試みる
        $response = $this->actingAs($userA)->get("/attendance/detail/{$attendanceB->id}");

        // 3. 検証 (Assert)
        // 他人のデータへのアクセスは拒否されること
        $response->assertStatus(404); 
        
        // ページの内容が何も表示されていないこと（エラーメッセージの有無は実装次第）
        $response->assertDontSee('2025-10-16');
    }
}
