<?php
/** 4.ステータス認証機能 */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User; 

class DateTimeRetrievalTest extends TestCase
{
    use RefreshDatabase;

    /**     勤怠打刻画面に現在日時がUIと同じ形式で表示されることをテスト     */
    public function test_attendance_screen_shows_current_datetime_in_correct_format()
    {
        // 現在日時を固定（テストの安定化のため）
        $now = Carbon::now();
        Carbon::setTestNow($now);

        // ユーザーを作成または取得
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        // 認証済ユーザーとして勤怠打刻画面にアクセス
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        // 期待する日時フォーマット
        $expectedDate = $now->format('Y年m月d日');
        $expectedTime = $now->format('H時i分s秒');

        // 画面に表示されていることを確認
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);

        // テスト終了後に時間の固定解除
        Carbon::setTestNow();
    }
}

