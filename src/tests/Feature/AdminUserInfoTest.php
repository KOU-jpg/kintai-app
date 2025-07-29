<?php
/** 14．ユーザー情報取得機能（管理者） */
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;

class AdminUserInfoTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->adminUser = User::where('role', 'admin')->first();
        if (!$this->adminUser) {
            $this->adminUser = User::factory()->create([
                'role' => 'admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password123'),
            ]);
        }
    }

    /** 管理者が全一般ユーザーの氏名・メールアドレスを確認できる */
    public function test_admin_attendance_detail_view_matches_database()
    {
        // 管理者ユーザーを取得
        $adminUser = User::where('role', 'admin')->first();
        $this->assertNotNull($adminUser, '管理者ユーザーが存在することを確認');
        $this->actingAs($adminUser);

        // 勤怠情報を一件取得（Seederであらかじめ作成されている想定）
        $attendance = Attendance::first();
        $this->assertNotNull($attendance, '勤怠データが存在すること');

        // 勤怠詳細画面へGETリクエスト
        $response = $this->get(route('attendance.detailByRole', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 表示内容の検証
        $response->assertSeeText($attendance->user->name ?? '');
        $response->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('n月j日'));
        $response->assertSee('value="' . \Carbon\Carbon::parse($attendance->shift_start)->format('H:i') . '"', false);
        $response->assertSee('value="' . \Carbon\Carbon::parse($attendance->shift_end)->format('H:i') . '"', false);
        $response->assertSeeText($attendance->note ?? '');
    }
    
    /**      「ユーザーの勤怠情報が正しく表示される     */
    public function test_admin_can_view_selected_user_attendance_list()
    {
        // 管理者ユーザーの取得または作成＆ログイン
        $adminUser = User::where('role', 'admin')->first();
        if (!$adminUser) {
            $adminUser = User::factory()->create([
                'role' => 'admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password123'),
            ]);
        }
        $this->actingAs($adminUser);

        // 一般ユーザーを1人取得（なければ作成）
        $user = User::where('role', 'user')->first();
        if (!$user) {
            $user = User::factory()->create(['role' => 'user']);
        }

        // 当月の年月(Y-m)
        $targetMonth = now()->format('Y-m');

        // そのユーザーの当月の勤怠データを取得（なければ作成）
        $attendance = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->first();

        if (!$attendance) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => now()->toDateString(),
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
                'break_minutes' => 60,
                'total_work_minutes' => 480,
            ]);
        }

        // 選択ユーザーの勤怠一覧ページにアクセス（ルート名は環境に応じて）
        $response = $this->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => $targetMonth]));

        $response->assertStatus(200);

        // ページタイトルとなるユーザー名が表示されていることを確認
        $response->assertSeeText($user->name);

        // 表示月のフォーマット確認（例: 2025年7月）
        $displayMonth = Carbon::parse($targetMonth . '-01')->format('Y年n月');
        $response->assertSeeText($displayMonth);

        // Bladeの「日付」カラム（m/d + 曜日）と一致しているかチェック
        $workDate = Carbon::parse($attendance->work_date);
        $response->assertSeeText($workDate->format('m/d'));
        $response->assertSeeText('(' . $workDate->isoFormat('ddd') . ')');

        // 「出勤」「退勤」「休憩」「合計」の各情報が表示されているかチェック

        // 出勤時間（例「09:00」）
        $response->assertSeeText($attendance->shift_start ? Carbon::parse($attendance->shift_start)->format('H:i') : '');

        // 退勤時間（例「18:00」）
        $response->assertSeeText($attendance->shift_end ? Carbon::parse($attendance->shift_end)->format('H:i') : '');

        // 休憩時間（例「01:00」）
        $breakTime = gmdate('H:i', ($attendance->break_minutes ?? 0) * 60);
        if ($breakTime !== '00:00') {
            $response->assertSeeText($breakTime);
        }

        // 合計勤務時間（例「08:00」）
        $workTime = gmdate('H:i', ($attendance->total_work_minutes ?? 0) * 60);
        if ($workTime !== '00:00') {
            $response->assertSeeText($workTime);
        }

        // 「詳細」リンクが出ていることを確認（URLは正確に検証しづらいのでリンクテキストの有無で検証）
        $response->assertSee('詳細');
    }

    /**      「前月」を押下した時に表示月の前月の情報が表示される     */
    public function test_admin_sees_previous_month_attendance_for_staff()
    
    {
        $this->actingAs($this->adminUser);
            
        // テスト対象ユーザーを1人取得（例えばrole=user のユーザーを取得）
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user, '一般ユーザーが存在することを確認');

        // 先月の年月取得（例：2025-06）
        $prevMonth = Carbon::today()->subMonth()->format('Y-m');

        // コントローラへGETリクエスト ※ monthパラメータに先月をセット
        $response = $this->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]));
        $response->assertStatus(200);

        // 画面に「YYYY年n月」の表示が含まれていることを検証（例：2025年6月）
        $expectedDisplayMonth = Carbon::parse($prevMonth . '-01')->format('Y年n月');
        $response->assertSeeText($expectedDisplayMonth);

        // 前月の勤怠データがあるかAttendanceテーブルで確認し、その勤怠日のユーザー名や勤務開始時刻を画面に検証

        $startOfPrevMonth = Carbon::parse($prevMonth . '-01')->startOfMonth()->toDateString();
        $endOfPrevMonth = Carbon::parse($prevMonth . '-01')->endOfMonth()->toDateString();

        // そのユーザーの前月勤怠を抽出
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfPrevMonth, $endOfPrevMonth])
            ->get();

        // データがあれば画面に該当ユーザー名・勤怠日付・勤務時間が表示されているか確認
        if ($attendances->isNotEmpty()) {
            foreach ($attendances as $attendance) {
                $response->assertSeeText($attendance->user->name);
                $response->assertSeeText(
                    Carbon::parse($attendance->work_date)->format('m/d') .
                    '(' . Carbon::parse($attendance->work_date)->isoFormat('ddd') . ')'
                );
                if ($attendance->shift_start) {
                    $response->assertSeeText(Carbon::parse($attendance->shift_start)->format('H:i'));
                }
                if ($attendance->shift_end) {
                    $response->assertSeeText(Carbon::parse($attendance->shift_end)->format('H:i'));
                }
            }
        } else {
            // もし前月勤怠データがない場合は、少なくともユーザー名は表示されていることを保証
            $response->assertSeeText($user->name);
            // 勤怠ないので時間表示は無しでもOK
        }
    }
/**      「翌月」を押下した時に表示月の翌月の情報が表示される     */
    public function test_admin_sees_next_month_attendance_for_staff()
    {
        // 管理者ユーザー取得＆ログイン
        $this->actingAs($this->adminUser);

        // テスト対象ユーザーを1名取得（roleが'user'のユーザー）
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user, '一般ユーザーが存在することを確認');

        // 今月から翌月の年月文字列を作成（例：2025-08）
        $nextMonth = Carbon::today()->addMonth()->format('Y-m');

        // 管理者がスタッフ別勤怠一覧画面へ翌月のmonthパラメータ付でアクセス
        $response = $this->get(route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]));
        $response->assertStatus(200);

        // 画面に翌月の年月（例：2025年8月）が表示されていることを検証
        $expectedDisplayMonth = Carbon::parse($nextMonth . '-01')->format('Y年n月');
        $response->assertSeeText($expectedDisplayMonth);

        // 翌月の勤怠データを抽出
        $startOfNextMonth = Carbon::parse($nextMonth . '-01')->startOfMonth()->toDateString();
        $endOfNextMonth = Carbon::parse($nextMonth . '-01')->endOfMonth()->toDateString();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfNextMonth, $endOfNextMonth])
            ->get();

        // データがあればそれぞれの日付・勤務時間が画面に表示されていることを確認
        if ($attendances->isNotEmpty()) {
            foreach ($attendances as $attendance) {
                $response->assertSeeText($attendance->user->name);
                $response->assertSeeText(
                    Carbon::parse($attendance->work_date)->format('m/d') .
                    '(' . Carbon::parse($attendance->work_date)->isoFormat('ddd') . ')'
                );
                if ($attendance->shift_start) {
                    $response->assertSeeText(Carbon::parse($attendance->shift_start)->format('H:i'));
                }
                if ($attendance->shift_end) {
                    $response->assertSeeText(Carbon::parse($attendance->shift_end)->format('H:i'));
                }
            }
        } else {
            // 勤怠データがなければユーザー名だけでも表示されていることを確認
            $response->assertSeeText($user->name);
        }
    }

/**      「詳細」を押下すると、その日の勤怠詳細画面に遷移する     */
    public function test_admin_can_navigate_to_attendance_detail_from_list()
    {
        // 1. 管理者ユーザーの取得・ログイン
        $adminUser = User::where('role', 'admin')->first();
        $this->assertNotNull($adminUser, '管理者ユーザーが存在することを確認');
        $this->actingAs($adminUser);

        // 2. 一般ユーザーを取得（または作成）
        $user = User::where('role', 'user')->first();
        $this->assertNotNull($user, '一般ユーザーが存在することを確認');

        // 3. 勤怠一覧画面で表示する今日の日付を取得
        $date = Carbon::today()->toDateString();

        // 勤怠データを取得。存在しなければテスト用に作成
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $date)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $date,
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
            ]);
        }

        // 4. 勤怠一覧画面へGETアクセス（日付付き）
        $listResponse = $this->get(route('admin.attendance.list', ['date' => $date]));
        $listResponse->assertStatus(200);

        // 5. 「詳細」ボタンのリンク先URLを自前で生成（Blade構成に合わせて）
        $detailUrl = route('attendance.detailByRole', ['id' => $attendance->id]);

        // 6. 詳細画面へGETアクセス（「詳細」ボタン押下をシミュレート）
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);

        // 7. 遷移先に表示される勤怠のユーザー名・日付・勤務開始・終了時間が画面に表示されているか検証
        $detailResponse->assertSeeText($attendance->user->name ?? '');
        $detailResponse->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('Y年'));
        $detailResponse->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('n月j日'));
        $detailResponse->assertSee('value="' . \Carbon\Carbon::parse($attendance->shift_start)->format('H:i') . '"', false);
        $detailResponse->assertSee('value="' . \Carbon\Carbon::parse($attendance->shift_end)->format('H:i') . '"', false);
    }





}

