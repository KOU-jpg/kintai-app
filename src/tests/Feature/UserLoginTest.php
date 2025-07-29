<?php
/** 2.ログイン認証機能（一般ユーザー） */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /**     テスト前に1ユーザーを登録する（共通のユーザーデータ）     */
    private function createUser()
    {
        return User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'), // パスワードはハッシュ化して登録
        ]);
    }

    /**     メールアドレスが未入力の場合、バリデーションエラーが返る     */
    public function test_email_is_required_on_login()
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**     パスワードが未入力の場合、バリデーションエラーが返る     */
    public function test_password_is_required_on_login()
    {
        $this->createUser();

        $response = $this->post('/login', [
            'email' => 'testuser@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**     登録内容と一致しない場合、認証失敗のバリデーションエラーが返る     */
    public function test_login_fails_with_invalid_credentials()
    {
        $this->createUser();

        $response = $this->from('/login')->post('/login', [
            'email' => 'wrongemail@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login'); // 元のログインページにリダイレクト
        $response->assertSessionHasErrors([
            'auth.failed' => 'ログイン情報が登録されていません',
        ]);
    }
}
