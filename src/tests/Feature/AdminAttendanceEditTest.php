<?php
/** 13．勤怠詳細情報取得・修正機能（管理者） */
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use DatabaseSeeder;




class AdminAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $attendance;

    protected function setUp(): void
    {
    parent::setUp();
    $this->seed(DatabaseSeeder::class);
    }

    /** 勤怠詳細画面に表示される内容が正しいことの確認 */
    public function test_admin_attendance_detail_view_matches_database()
{
    // 管理者ユーザー取得
    $adminUser = User::where('role', 'admin')->first();
    $this->actingAs($adminUser);


    // 既存の勤怠データをランダムに1件取得（なければテスト用作成）
    $attendance = Attendance::inRandomOrder()->first();


    // ブレードへ渡すbreaktimesは普通はリレーションモデルや別ロジックで取得する想定
    // ここはbreaktimesコレクションの取得や変換処理に合わせてください
    $breaktimes = collect(); // 空コレクションなら適宜実態に合わせて更新

    // 画面へGETリクエスト（勤怠詳細画面）
    $response = $this->get(route('attendance.detailByRole', ['id' => $attendance->id]));

    // HTTP 200 OK
    $response->assertStatus(200);

    // Bladeに渡された値の検証（レスポンスHTMLの断片がDB値と一致しているか）
    // --- ユーザー名 ---
    $response->assertSeeText($attendance->user->name ?? '');

    // --- 日付 ---
    $response->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('Y年'));
    $response->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('n月j日'));
    // hiddenのwork_dateはHTML上には見えないが、画面は確認視聴的に上だけでOK

    // --- 出勤・退勤時間 ---
    $shiftStart = $attendance->shift_start ? \Carbon\Carbon::parse($attendance->shift_start)->format('H:i') : '';
    $shiftEnd = $attendance->shift_end ? \Carbon\Carbon::parse($attendance->shift_end)->format('H:i') : '';

    $response->assertSee('value="' . $shiftStart . '"', false);
    $response->assertSee('value="' . $shiftEnd . '"', false);

    // --- 休憩時間（breaktimes） ---
    // breaktimesがある場合はすべてチェック
    foreach ($breaktimes as $i => $breaktime) {
        $breakStart = $breaktime->start_time ? \Carbon\Carbon::parse($breaktime->start_time)->format('H:i') : '';
        $breakEnd = $breaktime->end_time ? \Carbon\Carbon::parse($breaktime->end_time)->format('H:i') : '';

        $response->assertSee('name="breaktimes[' . $i . '][start_time]"', false);
        $response->assertSee('value="' . $breakStart . '"', false);

        $response->assertSee('name="breaktimes[' . $i . '][end_time]"', false);
        $response->assertSee('value="' . $breakEnd . '"', false);
    }

    // --- 備考 ---
    $note = old('note', $attendance->note ?? '');
    $response->assertSeeText($note);
}
/** 出勤時間が退勤時間より後の場合はバリデーションエラーになること */
public function test_validation_error_when_shift_start_after_shift_end()
{
    $adminUser = User::where('role', 'admin')->first();
    $this->actingAs($adminUser);

    $attendance = Attendance::inRandomOrder()->first();
    $this->assertNotNull($attendance, '勤怠データが存在することを確認');

    $postData = [
        'shift_start' => '19:00',  // 退勤より後
        'shift_end' => '18:00',
        'breaktimes' => [],
        'note' => $attendance->note ?? 'テスト備考',
    ];

    $response = $this->from(route('attendance.detailByRole', ['id' => $attendance->id]))
                     ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);

        // バリデーションエラーのためリダイレクト（302）されることを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('shift_end');  // 'shift_end'にエラーがあるかチェック

        // さらにエラーメッセージが想定どおりか確認したい場合
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('shift_end'));
}
/** 休憩開始時間が退勤時間より後の場合はバリデーションエラーになること */
public function test_validation_error_when_break_start_after_shift_end()
{
    $adminUser = User::where('role', 'admin')->first();
    $this->actingAs($adminUser);

    $attendance = Attendance::inRandomOrder()->first();
    $this->assertNotNull($attendance, '勤怠データが存在することを確認');

    $postData = [
        'shift_start' => '09:00',
        'shift_end' => '18:00',
        'breaktimes' => [
            ['start_time' => '19:00', 'end_time' => '19:30'],  // 退勤より後
        ],
        'note' => $attendance->note ?? 'テスト備考',
    ];

    $response = $this->from(route('attendance.detailByRole', ['id' => $attendance->id]))
                     ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);

        // バリデーションエラーでリダイレクト（302）
        $response->assertStatus(302);

        // セッションに休憩開始時間のエラーが含まれているかチェック。breaktimes.0.start_time にエラーが入っているか確認
        $response->assertSessionHasErrors('breaktimes.0.start_time');

        // 実際のエラーメッセージを確認したい場合は以下のように書く
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('休憩時間が不適切な値です', $errors->first('breaktimes.0.start_time'));
}

/** 休憩終了時間が退勤時間より後の場合はバリデーションエラーになること */
public function test_validation_error_when_break_end_after_shift_end()
{
    $adminUser = User::where('role', 'admin')->first();
    $this->actingAs($adminUser);

    $attendance = Attendance::inRandomOrder()->first();
    $this->assertNotNull($attendance, '勤怠データが存在することを確認');

    $postData = [
        'shift_start' => '09:00',
        'shift_end' => '18:00',
        'breaktimes' => [
            ['start_time' => '12:00', 'end_time' => '19:00'],  // 退勤より後
        ],
        'note' => $attendance->note ?? 'テスト備考',
    ];

    $response = $this->from(route('attendance.detailByRole', ['id' => $attendance->id]))
                     ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);

        // バリデーションエラーでリダイレクトされることを確認
        $response->assertStatus(302);

        // セッションに休憩終了時間のエラーが含まれているか確認
        $response->assertSessionHasErrors('breaktimes.0.end_time');

        // エラーメッセージが期待通りか確認（必要に応じて）
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('breaktimes.0.end_time'));
}

/** 備考欄が未入力の場合はバリデーションエラーになること */
public function test_validation_error_when_note_is_empty()
{
    $adminUser = User::where('role', 'admin')->first();
    $this->actingAs($adminUser);

    $attendance = Attendance::inRandomOrder()->first();
    $this->assertNotNull($attendance, '勤怠データが存在することを確認');

    $postData = [
        'shift_start' => '09:00',
        'shift_end' => '18:00',
        'breaktimes' => [],
        'note' => '',  // 空欄
    ];

    $response = $this->from(route('attendance.detailByRole', ['id' => $attendance->id]))
                     ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);


        // バリデーションエラーによるリダイレクトを期待
        $response->assertStatus(302);

        // セッションに 'note' フィールドのエラーがあることを検証
        $response->assertSessionHasErrors('note');

        // エラーメッセージが期待通りか検証（必要に応じて）
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('備考を記入してください', $errors->first('note'));
}
    

}
