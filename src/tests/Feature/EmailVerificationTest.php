<?php
/** 16.メール認証機能 */
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**     会員登録後、認証メールが送信される     */
    public function test_registration_sends_email_verification_notification()
    {
        Notification::fake();

        // テスト用ユーザーデータ
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 会員登録を実行
        $this->post(route('register'), $userData);

        // DBから登録ユーザーを取得
        $user = User::where('email', $userData['email'])->first();

        // ここで「認証メールが送信されたか」を検証！
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    /**     メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する     */
    public function test_email_verification_link_verifies_user_and_redirects()
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify', // 署名付き認証URL
            now()->addMinutes(60), 
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 認証URLにログイン済みとしてアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        Event::assertDispatched(\Illuminate\Auth\Events\Verified::class);

        $response->assertRedirect('/attendance');
    }

    /**     メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する     */
        public function test_email_verification_completes_and_redirects_to_attendance()
    {
        Event::fake();

        // 未認証ユーザーを作成
        $user = User::factory()->unverified()->create();

        // 署名付きメール認証URLを生成（'verification.verify' は認証ルート名）
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 認証URLにログイン状態でアクセス
        $response = $this->actingAs($user)->get($verificationUrl);

        // メール認証が完了しているかの検証
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // Verifiedイベントが発火しているか確認
        Event::assertDispatched(Verified::class);

        // 勤怠登録画面へのリダイレクト確認
        $response->assertRedirect(route('attendance'));
    }

}
