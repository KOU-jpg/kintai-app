<?php
/** 6.出勤機能 */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;


class AttendanceStartTest extends TestCase
{
    use RefreshDatabase;

    /**     勤怠レコード付きユーザー作成ヘルパー（初期は勤務外）     */
    private function createUserWithAttendance(string $workStatus = 'before_work'): User
    {
        $user = User::factory()->create([
            'email' => $workStatus . '@example.com',
            'password' => bcrypt('password123'),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'work_status' => $workStatus,
            'shift_start' => null,
        ]);

        return $user;
    }

    /**     勤務外ユーザーの勤怠画面に「出勤」ボタンが表示されていることを確認     */
    public function test_off_work_user_sees_clock_in_button()
    {
        $user = $this->createUserWithAttendance('before_work');
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤');
    }

    /**     出勤処理を行い、work_status が 'working' に更新され、画面に「勤務中」と表示されることを確認     */
    public function test_off_work_user_can_see_clock_in_button_and_clock_in_changes_status()
    {
        // 勤務外ステータスのユーザー作成
        $user = $this->createUserWithAttendance('before_work');
    
        // 認証状態にする
        $this->actingAs($user);

        // 勤怠画面にアクセスして「出勤」ボタンがあることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 出勤処理のPOSTリクエスト
        $response = $this->post('/attendance/clock-in');
        $response->assertRedirect('/attendance');

        // 勤怠テーブルのステータスが 'working' に更新されていることをDBで検証
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'work_status' => 'working',
        ]);

        // 改めて勤怠画面にアクセスし、「勤務中」表示を確認
        $response = $this->get('/attendance');
        $response->assertSee('勤務中');
    }

    /**     退勤済ユーザーは「出勤」ボタンが表示されないことを確認     */
    public function test_checked_out_user_does_not_see_clock_in_button()
    {
        $user = $this->createUserWithAttendance('after_work');
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**     出勤時刻が勤怠一覧画面に正しく表示されていることを確認     */
    public function test_clock_in_time_is_displayed_in_attendance_list()
    {
        $user = $this->createUserWithAttendance('before_work');
        $this->actingAs($user);

        // 出勤処理を実行
        $this->post('/attendance/clock-in');

        // 勤怠一覧画面を表示
        $response = $this->get('/attendance/list');  // 勤怠一覧画面のURLに合わせてください

        $response->assertStatus(200);

        // 出勤時刻が画面に表示されているか確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance->shift_start);

        // 画面に表示される日時のフォーマットは必要に応じて調整
        $expectedTime = $attendance->shift_start->format('H:i');

        $response->assertSee($expectedTime);
    }
}

