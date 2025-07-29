<?php
/** 10.勤怠一覧情報取得機能（一般ユーザー） */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    
    /**     テスト用ユーザー作成と複数勤怠データ作成のヘルパー     */
    private function createUserWithAttendance(string $workDate): array
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'shift_start' => Carbon::parse($workDate)->setTime(9, 0, 0),
            'shift_end' => Carbon::parse($workDate)->setTime(18, 0, 0),
            'break_minutes' => 60,
            'work_status' => 'after_work',
        ]);

        return [$user, $attendance];
    }

    /**     勤怠詳細画面にログインユーザーの名前が表示されていることを確認     */
    public function test_attendance_detail_displays_user_name()
    {
        [$user, $attendance] = $this->createUserWithAttendance('2025-07-25');

        $this->actingAs($user);

        $response = $this->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        // ユーザー名が画面に表示されていること
        $response->assertSeeText($user->name);
    }

    /**     出勤・退勤時刻が表示されていることを確認     */
    public function test_attendance_detail_displays_clock_in_and_out_times()
    {
        [$user, $attendance] = $this->createUserWithAttendance('2025-07-25');

        $this->actingAs($user);

        $response = $this->get("/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $shiftStart = $attendance->shift_start->format('H:i');
        $shiftEnd = $attendance->shift_end->format('H:i');


        // inputタグのvalueで時刻が含まれているかassertSeeで判定
        $response->assertSee($shiftStart);
        $response->assertSee($shiftEnd);
    }

    /**     休憩時間が表示されていることを確認     */
    public function test_attendance_detail_displays_break_times()
    {
        [$user, $attendance] = $this->createUserWithAttendance('2025-07-25');
        
        $this->actingAs($user);

        $response = $this->get("/attendance/{$attendance->id}");
        $response->assertStatus(200);

        // 休憩レコード群を取得（ここが最重要）
        $breaktimes = $attendance->breakTimes;

        foreach ($breaktimes as $index => $breaktime) {
            $startTime = \Carbon\Carbon::parse($breaktime->start_time)->format('H:i');
            $endTime = \Carbon\Carbon::parse($breaktime->end_time)->format('H:i');

            $response->assertSee('value="' . $startTime . '"');
            $response->assertSee('value="' . $endTime . '"');
        }
    }

}
