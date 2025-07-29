<?php
/** 12．勤怠一覧情報取得機能（管理者） */
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;


class AdminAttendanceListTest extends TestCase
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

//遷移した際に現在の日付が表示される、勤怠データがあるユーザーの名前が画面に出ているか確認
public function test_admin_can_see_attendance_list_for_date()
{
    // 管理者ユーザー取得か作成
    $adminUser = User::where('role', 'admin')->first();
    if (!$adminUser) {
        $adminUser = User::factory()->create(['role' => 'admin']);
    }
    $this->actingAs($adminUser);

    $date = Carbon::today()->toDateString();

    // 「当日」の勤怠データを持つ一般ユーザーを複数作成する
    $users = User::factory()->count(3)->create(['role' => 'user']);

    foreach ($users as $user) {
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date,
            'shift_start' => '09:00:00',
            'shift_end' => '18:00:00',
        ]);
    }

    // 日付パラメータ付きで勤怠一覧へアクセス
    $response = $this->get(route('admin.attendance.list', ['date' => $date]));
    $response->assertStatus(200);
    $response->assertViewHas('adminData');

    // 画面に指定日付が表示されていることを確認
    $expectedDateString = Carbon::parse($date)->isoFormat('YYYY/M/D');
    $response->assertSeeText($expectedDateString);

    // 勤怠データがあるユーザーの名前が画面に出ているか確認
    foreach ($users as $user) {
        $response->assertSeeText($user->name);
    }
}

    /**    管理者が勤怠一覧画面で「前日」ボタンを押し前日の勤怠を確認できる   */
    public function test_admin_can_view_previous_day_attendance()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::today();
        $prevDay = $today->copy()->subDay()->format('Y-m-d');

        // 前日勤怠データ用の一般ユーザーを作成
        $users = User::factory()->count(3)->create(['role' => 'user']);

        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $prevDay,
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
                'break_minutes' => 60,
            ]);
        }

        // 「前日」押下に相当する 動作（query 'date'に前日をセット）
        $response = $this->get(route('admin.attendance.list', ['date' => $prevDay]));

        $response->assertStatus(200);

        // 表示される日付チェック（例 "2025/7/27"）
        $response->assertSeeText(Carbon::parse($prevDay)->isoFormat('YYYY/M/D'));

        // 勤怠データのあるユーザー名が画面にあることを確認
        foreach ($users as $user) {
            $response->assertSeeText($user->name);
            $response->assertSeeText('09:00');
            $response->assertSeeText('18:00');
        }
    }

    /**    管理者が勤怠一覧画面で「翌日」ボタンを押し翌日の勤怠を確認できる    */
    public function test_admin_can_view_next_day_attendance()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::today();
        $nextDay = $today->copy()->addDay()->format('Y-m-d');

        $users = User::factory()->count(3)->create(['role' => 'user']);

        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $nextDay,
                'shift_start' => '08:30:00',
                'shift_end' => '17:30:00',
                'break_minutes' => 30,
            ]);
        }

        // 「翌日」押下に相当する動作（query 'date'に翌日をセット）
        $response = $this->get(route('admin.attendance.list', ['date' => $nextDay]));

        $response->assertStatus(200);

        // 表示される日付チェック
        $response->assertSeeText(Carbon::parse($nextDay)->isoFormat('YYYY/M/D'));

        // 勤怠データのあるユーザー名が画面にあること
        foreach ($users as $user) {
            $response->assertSeeText($user->name);
            $response->assertSeeText('08:30');
            $response->assertSeeText('17:30');
        }
    }
}
