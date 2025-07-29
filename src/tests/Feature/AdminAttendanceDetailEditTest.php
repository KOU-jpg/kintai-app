<?php
/** 15．勤怠情報修正機能（管理者） */
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AdminAttendanceDetailEditTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);  // CSRF除外

        // 管理者ユーザー作成
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

    $this->actingAs($this->adminUser);

        // 一般ユーザー2人を作成
        $user1 = User::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'role' => 'user',
            'email_verified_at' => now(), 
            'password' => bcrypt('password123'),
        ]);
        $user2 = User::create([
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'role' => 'user',
            'email_verified_at' => now(), 
            'password' => bcrypt('password123'),
        ]);

        // 勤怠情報を直接作成（work_date必須）
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-28 09:00:00'),
            'shift_end' => Carbon::parse('2025-07-28 18:00:00'),
            'break_minutes' => 60,
            'total_work_minutes' => 480,
            'work_status' => 'after_work',
            'note' => null,
        ]);
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-28 10:00:00'),
            'shift_end' => Carbon::parse('2025-07-28 19:00:00'),
            'break_minutes' => 30,
            'total_work_minutes' => 480,
            'work_status' => 'after_work',
            'note' => null,
        ]);
        $attendanceApproved = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-27 09:00:00'),
            'shift_end' => Carbon::parse('2025-07-27 18:00:00'),
            'break_minutes' => 60,
            'total_work_minutes' => 480,
            'work_status' => 'after_work',
            'note' => null,
        ]);

        // 承認待ち修正申請2件
        AttendanceRequest::create([
            'attendance_id' => $attendance1->id,
            'user_id' => $user1->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-28 09:30:00'),
            'shift_end' => Carbon::parse('2025-07-28 18:30:00'),
            'total_work_minutes' => 450,
            'break_time' => json_encode([ ['start_time' => '12:00', 'end_time' => '13:00'] ]),
            'break_minutes' => 60,
            'duration_minutes' => 540,
            'note' => '未承認の修正申請1',
            'request_status' => 'pending',
        ]);
        AttendanceRequest::create([
            'attendance_id' => $attendance2->id,
            'user_id' => $user2->id,
            'work_date' => Carbon::today()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-28 10:00:00'),
            'shift_end' => Carbon::parse('2025-07-28 19:00:00'),
            'total_work_minutes' => 480,
            'break_time' => json_encode([ ['start_time' => '13:00', 'end_time' => '13:30'] ]),
            'break_minutes' => 30,
            'duration_minutes' => 540,
            'note' => '未承認の修正申請2',
            'request_status' => 'pending',
        ]);

        // 承認済み修正申請1件
        AttendanceRequest::create([
            'attendance_id' => $attendanceApproved->id,
            'user_id' => $user1->id,
            'work_date' => Carbon::today()->subDay()->format('Y-m-d'),
            'shift_start' => Carbon::parse('2025-07-27 09:00:00'),
            'shift_end' => Carbon::parse('2025-07-27 18:00:00'),
            'total_work_minutes' => 480,
            'break_time' => json_encode([ ['start_time' => '12:00', 'end_time' => '13:00'] ]),
            'break_minutes' => 60,
            'duration_minutes' => 540,
            'note' => '承認済みの修正申請',
            'request_status' => 'approved',
        ]);

        // 管理者ログイン状態をセット
        $this->actingAs($this->adminUser);
    }



//修正申請の承認処理が正しく行われる
//承認待ちの修正申請が全て表示されている
    public function testAdminCanSeePendingRequests()
{        // 管理者ログイン状態をセット
        $this->actingAs($this->adminUser);
        $response = $this->get(route('request', ['status' => 'pending']));
if ($response->getStatusCode() === 302) {
    echo 'Redirect to: ' . $response->headers->get('Location');
}

        $response->assertStatus(200);

        // ページに未承認申請のメモが表示されることを確認
        $response->assertSee('未承認の修正申請1');
        $response->assertSee('未承認の修正申請2');

        // 承認済み申請のメモは表示されない
        $response->assertDontSee('承認済みの修正申請');
    }


//承認済みの修正申請が全て表示されている
public function testAdminCanSeeApprovedRequests()
{
    // 管理者ログイン状態
    $this->actingAs($this->adminUser);

    // 承認済みの修正申請一覧ページを取得
    $response = $this->get(route('request', ['status' => 'approved']));
    
    if ($response->getStatusCode() === 302) {
        echo 'Redirect to: ' . $response->headers->get('Location');
    }

    $response->assertStatus(200);

    // setUpで作成した承認済み修正申請のメモがページにあることを確認
    $response->assertSee('承認済みの修正申請');

    // 未承認の申請は表示されていないことを確認（念のため）
    $response->assertDontSee('未承認の修正申請1');
    $response->assertDontSee('未承認の修正申請2');
}


//修正申請の詳細内容が正しく表示されている
public function testAdminCanViewCorrectAttendanceRequestDetail()
{
    // 管理者ログイン状態
    $this->actingAs($this->adminUser);

    $attendance = Attendance::with('user')
        ->where('user_id', $this->adminUser->id)
        ->orWhereHas('attendanceRequest', function($q) {
            $q->where('request_status', 'pending');
        })
        ->first();

    // テスト対象が確実に取得できているか確認
    $this->assertNotNull($attendance);

    // 詳細画面アクセス
    $response = $this->get(route('attendance.detailByRole', ['id' => $attendance->id]));

    $response->assertStatus(200);

    // 申請中の修正申請のメモが表示されていることを確認
    $attendanceRequest = $attendance->attendanceRequest;
    if ($attendanceRequest && $attendanceRequest->request_status === 'pending') {
        $response->assertSee($attendanceRequest->note);

        // 出勤・退勤時刻（H:iフォーマット）を含むことも確認
        $response->assertSee(\Carbon\Carbon::parse($attendanceRequest->shift_start)->format('H:i'));
        $response->assertSee(\Carbon\Carbon::parse($attendanceRequest->shift_end)->format('H:i'));
}


}
public function testAdminCanApproveAttendanceRequestAndAttendanceIsUpdated()
{
    // 承認待ちの修正申請を1件取得
    $attendanceRequest = \App\Models\AttendanceRequest::where('request_status', 'pending')->first();
    $attendance = $attendanceRequest->attendance;

    // 管理者でログイン
    $this->actingAs($this->adminUser);

    // 承認リクエストを送信（routeは適宜調整してください）
    $response = $this->post(route('admin.request.update', ['attendance_correct_request' => $attendanceRequest->id]));

    // 正常レスポンスを確認
    $response->assertStatus(302); // 成功時リダイレクトを想定

    // 修正申請が承認済みステータスになっていることを確認
    $attendanceRequest->refresh();
    $this->assertEquals('approved', $attendanceRequest->request_status);

    // 勤怠情報が申請内容で上書きされていることを確認
    $attendance->refresh();
    $this->assertEquals($attendanceRequest->shift_start, $attendance->shift_start);
    $this->assertEquals($attendanceRequest->shift_end, $attendance->shift_end);
    $this->assertEquals($attendanceRequest->break_minutes, $attendance->break_minutes);
    $this->assertEquals($attendanceRequest->total_work_minutes, $attendance->total_work_minutes);
    $this->assertEquals($attendanceRequest->note, $attendance->note);

    // 必要に応じてページ遷移先やflashメッセージなども追加アサート可能
}

}