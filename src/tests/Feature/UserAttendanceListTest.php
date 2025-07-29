<?php
/** 9.勤怠一覧情報取得機能（一般ユーザー） */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**     テスト用ユーザー作成と複数勤怠データ作成のヘルパー     */
    private function createUserWithAttendances(array $dates): User
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        foreach ($dates as $date) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date,
                'work_status' => 'working',
                'shift_start' => Carbon::parse($date)->setTime(9, 0),
                'shift_end' => Carbon::parse($date)->setTime(18, 0),
            ]);
        }

        return $user;
    }

    /**     自分の勤怠情報が全て表示されること     */
    public function test_user_sees_all_their_attendance_records()
    {
        $dates = [
            '2025-07-01',
            '2025-07-02',
            '2025-07-03',
        ];
        $user = $this->createUserWithAttendances($dates);
        $this->actingAs($user);
        $response = $this->get('/attendance/list'); // 勤怠一覧ページのURL
        $response->assertStatus(200);

        foreach ($dates as $date) {
            // 勤怠日の表示　表示例：「07/01(火)」
             $carbonDate = \Carbon\Carbon::parse($date);
            $expectedDateString = $carbonDate->format('m/d') . '(' . $carbonDate->isoFormat('ddd') . ')';
            
            $response->assertSee($expectedDateString);
            // 出勤開始時間の表示（例: 09:00）
            $response->assertSee('09:00');
            // 退勤時刻（例: 18:00）
            $response->assertSee('18:00');
        }
    }

    /**     勤怠一覧画面に遷移した際に現在の月が表示されていること     */
    public function test_current_month_is_displayed_on_attendance_list()
    {
        $user = $this->createUserWithAttendances([]);
        $this->actingAs($user);
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 画面に現在の月(例: "2025年07月")が表示されてるか
        $expectedMonth = Carbon::now()->format('Y年n月'); 
        $response->assertSee($expectedMonth);
    }

    /**     「前月」ボタンを押した時に前月の情報が表示されること     */
    public function test_previous_month_shows_previous_month_attendance_data()
    {
        $currentMonthDate = Carbon::now();
        $prevMonthDate = $currentMonthDate->copy()->subMonth();

        // 前月の勤怠データを作成
        $prevMonthDates = [
            $prevMonthDate->copy()->startOfMonth()->toDateString(),
            $prevMonthDate->copy()->startOfMonth()->addDays(1)->toDateString(),
        ];

        $user = $this->createUserWithAttendances($prevMonthDates);

        $this->actingAs($user);

        // 「前月」ボタンを押すイメージでクエリパラメータやPOSTがあれば調整
        // ここではGETパラメータ ?month=YYYY-MM で管理する例
        $response = $this->get('/attendance/list?month=' . $prevMonthDate->format('Y-m'));

        $response->assertStatus(200);

        // 画面に前月の日付が表示されていることを確認
        foreach ($prevMonthDates as $date) {
            $carbonDate = \Carbon\Carbon::parse($date);
            // 例: 06/01(月)
            $expectedDateString = $carbonDate->format('m/d') . '(' . $carbonDate->isoFormat('ddd') . ')';
            $response->assertSee($expectedDateString);
        }

        // 画面に前月が表示されていることを確認 (例: "2025年06月")
        $expectedPrevMonth = $prevMonthDate->format('Y年n月');
        $response->assertSee($expectedPrevMonth);
    }

    /**     「翌月」ボタンを押した時に翌月の情報が表示されること     */
    public function test_next_month_shows_next_month_attendance_data()
    {
        $currentMonthDate = Carbon::now();
        $nextMonthDate = $currentMonthDate->copy()->addMonth();

        // 翌月の勤怠データを作成
        $nextMonthDates = [
            $nextMonthDate->copy()->startOfMonth()->toDateString(),
            $nextMonthDate->copy()->startOfMonth()->addDays(1)->toDateString(),
        ];

        $user = $this->createUserWithAttendances($nextMonthDates);
        $this->actingAs($user);

        // 「翌月」ボタン押下を模したアクセス。GETパラメータで月指定の例
        $response = $this->get('/attendance/list?month=' . $nextMonthDate->format('Y-m'));
        $response->assertStatus(200);

        foreach ($nextMonthDates as $date) {
            $carbonDate = \Carbon\Carbon::parse($date);
            // 画面の表示形式「08/01（土）」に合わせる
            $expectedDateString = $carbonDate->format('m/d') . '(' . $carbonDate->isoFormat('ddd') . ')';
            $response->assertSee($expectedDateString);
        }

        // 月表示はゼロ埋めなしを使う（例：2025年8月）
        $expectedNextMonth = $nextMonthDate->format('Y年n月');
        $response->assertSee($expectedNextMonth);
    }


    /**     「詳細」ボタン押下でその日の勤怠詳細画面に遷移すること     */
    public function test_clicking_detail_button_redirects_to_attendance_detail_page()
    {
        $date = Carbon::now()->toDateString();
        $user = $this->createUserWithAttendances([$date]);
        $this->actingAs($user);

        // 勤怠一覧画面を開く
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // 勤怠レコードIDを取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('work_date', $date)
                                ->first();

        // ルートが /attendance/{id} の場合のURL
        $detailUrl = "/attendance/{$attendance->id}";

        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);

        // 日付の画面表示フォーマットに合わせてチェック
        $carbonDate = Carbon::parse($date);
        $detailResponse->assertSee($carbonDate->format('Y年'));
        $detailResponse->assertSee($carbonDate->format('n月j日'));
    }
}