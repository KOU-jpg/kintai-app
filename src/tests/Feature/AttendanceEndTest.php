<?php
/** 8.退勤機能 */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceEndTest extends TestCase
{
    use RefreshDatabase;
    /**     勤怠レコード付きユーザー作成ヘルパー（初期は勤務外）     */
    private function createUserWithAttendance(string $workStatus = 'working'): User
    {
        $user = User::factory()->create([
            'email' => $workStatus . '@example.com',
            'password' => bcrypt('password123'),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'work_status' => $workStatus,
            'shift_start' => Carbon::today()->setTime(9, 0, 0), // 例: 9:00出勤
            'shift_end' => null,
        ]);

        return $user;
    }

    /**     勤務中ユーザーの勤怠画面に「退勤」ボタンが表示されていることを確認     */
    public function test_user_sees_clock_out_button_while_working()
    {
        $user = $this->createUserWithAttendance('working');
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤');
    }

    /**     退勤処理を行い、work_status が 'after_work' に更新され、画面に「退勤済」と表示されることを確認
     */
    public function test_user_can_clock_out_and_status_changes_to_after_work()
    {
        $user = $this->createUserWithAttendance('working');
        $this->actingAs($user);

        $response = $this->post('/attendance/clock-out');

        // 処理後のリダイレクトを確認
        $response->assertRedirect('/attendance');

        // DBの勤怠ステータスと退勤時刻が更新されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('after_work', $attendance->work_status);
        $this->assertNotNull($attendance->shift_end);

        // 改めて勤怠画面を取得し、「退勤済」の表示を確認
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /**     勤務外ユーザーで出勤・退勤処理を行い、勤怠一覧画面に退勤時刻が正確に表示されることを確認     */
    public function test_clock_out_time_is_displayed_in_attendance_list()
    {
        $user = $this->createUserWithAttendance('before_work');
        $this->actingAs($user);

        // まず出勤処理をしてステータスを 'working' にする（POST先は適宜）
        $this->post('/attendance/clock-in');

        // 次に退勤処理を実行
        $this->post('/attendance/clock-out');

        // 勤怠一覧画面を取得
        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->shift_end);

        // 画面に表示されている退勤時刻（例: 'H:i' フォーマット）を確認
        $expectedShiftEnd = $attendance->shift_end->format('H:i');

        $response->assertSee($expectedShiftEnd);
    }
}
