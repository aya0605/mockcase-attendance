<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase; 

    // ヘルパー: 管理者ユーザーを作成 (role=1を想定)
    protected function createAdminUser()
    {
        return User::factory()->create(['role' => 1]); 
    }

    // ヘルパー: 一般ユーザーを作成 (role=0を想定)
    protected function createGeneralUser()
    {
        return User::factory()->create(['role' => 0]);
    }
    
    //---------------------------------------------------------
    // (3) テストメソッドの追加
    //---------------------------------------------------------

    /** @test */
    public function ログイン済みの管理者はダッシュボードにアクセスできる()
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200); 
        // 実際のダッシュボードに含まれる管理者向けのテキストで確認
        $response->assertSee('勤怠一覧'); 
    }

    /** @test */
    public function ログイン済みの一般ユーザーは管理者ダッシュボードにアクセスできない()
    {
        $user = $this->createGeneralUser();
        
        $response = $this->actingAs($user)->get('/admin/dashboard');

        // 権限エラー(403)または、一般ユーザーのホーム画面へリダイレクトされることを検証
        // 実装によりますが、ここでは権限エラー(403)を検証します。
        $response->assertStatus(403); 
    }

    /** @test */
    public function ゲストユーザーは管理者ダッシュボードにアクセスできずログイン画面にリダイレクトされる()
    {
        $response = $this->get('/admin/dashboard');

        // 認証ミドルウェアによってログイン画面へリダイレクトされることを検証
        $response->assertRedirect('/login'); 
    }
}
