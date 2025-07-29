<?php
/** 11.勤怠詳細情報修正機能（一般ユーザー） */
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;



use App\Models\AttendanceRequest;

class UserAttendanceDetailEditTest extends TestCase
{
    protected $user;
    protected $attendance;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::where('role', '!=', 'admin')->inRandomOrder()->first();

        if (!$this->user) {
            $this->user = User::factory()->create([
                'role' => 'user',
            ]);
        }

        $this->attendance = Attendance::where('user_id', $this->user->id)->inRandomOrder()->first();

        if (!$this->attendance) {
            $this->attendance = Attendance::factory()->create([
                'user_id' => $this->user->id,
                'work_date' => now()->format('Y-m-d'),
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('attendance.detailByRole', ['id' => $this->attendance->id]));
        $response->assertStatus(200);
    }

    public function test_clock_in_after_clock_out_shows_validation_error()
    {
        // 出勤時間を19:00、退勤時間を18:00にして意図的にエラーを起こす
        $postData = [
            'shift_start' => '19:00',
            'shift_end' => '18:00',
            'breaktimes' => [],
            'note' => $this->attendance->note ?? 'テスト備考',
        ];

        // ログインユーザーとしてPOST送信
        $response = $this->actingAs($this->user)->post(route('attendance.edit', ['id' => $this->attendance->id]), $postData);

        // バリデーションエラーのためリダイレクト（302）されることを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors('shift_end');  // 'shift_end'にエラーがあるかチェック

        // さらにエラーメッセージが想定どおりか確認したい場合
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('shift_end'));
    }

    public function test_break_start_after_shift_end_shows_validation_error()
    {
        $postData = [
            'shift_start' => '09:00',
            'shift_end' => '18:00',
            'breaktimes' => [
                [
                    'start_time' => '18:30',  // 退勤時間より後 → バリデーションエラー対象
                    'end_time' => '18:45',
                ],
            ],
            'note' => $this->attendance->note ?? 'テスト備考',
        ];

        // ログインユーザーとしてPOSTリクエスト送信
        $response = $this->actingAs($this->user)->post(route('attendance.edit', ['id' => $this->attendance->id]), $postData);

        // バリデーションエラーでリダイレクト（302）
        $response->assertStatus(302);

        // セッションに休憩開始時間のエラーが含まれているかチェック。breaktimes.0.start_time にエラーが入っているか確認
        $response->assertSessionHasErrors('breaktimes.0.start_time');

        // 実際のエラーメッセージを確認したい場合は以下のように書く
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('休憩時間が不適切な値です', $errors->first('breaktimes.0.start_time'));
    }

    public function test_break_end_after_shift_end_shows_validation_error()
    {
        $postData = [
            'shift_start' => '09:00',
            'shift_end' => '18:00',
            'breaktimes' => [
                [
                    'start_time' => '17:30', // 正常な開始時間
                    'end_time' => '18:30',   // 退勤時間より後 → エラー対象
                ],
            ],
            'note' => $this->attendance->note ?? 'テスト備考',
        ];

        // 勤怠情報が登録されたユーザーとしてログインし、POSTリクエスト送信
        $response = $this->actingAs($this->user)->post(route('attendance.edit', ['id' => $this->attendance->id]), $postData);

        // バリデーションエラーでリダイレクトされることを確認
        $response->assertStatus(302);

        // セッションに休憩終了時間のエラーが含まれているか確認
        $response->assertSessionHasErrors('breaktimes.0.end_time');

        // エラーメッセージが期待通りか確認（必要に応じて）
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('breaktimes.0.end_time'));
    }

     //備考欄が未入力の場合のエラーメッセージが表示される
    public function test_note_is_required_validation_error()
    {
        $postData = [
            'shift_start' => '09:00',
            'shift_end' => '18:00',
            'breaktimes' => [],
            'note' => '',  // 備考欄を空にしてエラーを発生させる
        ];

        // ログインユーザーとしてPOST送信
        $response = $this->actingAs($this->user)->post(route('attendance.edit', ['id' => $this->attendance->id]), $postData);

        // バリデーションエラーによるリダイレクトを期待
        $response->assertStatus(302);

        // セッションに 'note' フィールドのエラーがあることを検証
        $response->assertSessionHasErrors('note');

        // エラーメッセージが期待通りか検証（必要に応じて）
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertEquals('備考を記入してください', $errors->first('note'));
    }

     //修正申請処理が実行される
    public function test_after_post_attendance_edit_the_request_page_is_displayed()
    {
        $postData = [
            'shift_start' => '10:00',
            'shift_end'   => '19:00',
            'breaktimes'  => [
                [
                    'start_time' => '16:30',
                    'end_time'   => '17:30',
                ],
            ],
            'note'        => $this->attendance->note ?? '勤務時間修正の申請です',
        ];

                // 1. ユーザーとして勤怠編集申請POST送信
        $response = $this->actingAs($this->user)
            ->post(route('attendance.edit', ['id' => $this->attendance->id]), $postData);

        // 2. 管理者ユーザー取得
        $adminUser = User::where('role', 'admin')->first();
        $this->assertNotNull($adminUser, "管理者ユーザーが存在すること");

        // 申請テーブルから該当申請を取得
        $attendanceRequest = AttendanceRequest::where('attendance_id', $this->attendance->id)->first();
        $this->assertNotNull($attendanceRequest, "申請レコードが存在すること");

        // 管理者承認画面にアクセス（勤怠IDをルートパラメータに渡す）
        $adminResponse = $this->actingAs($adminUser)
            ->get(route('admin.request.approveForm', ['attendance_correct_request' => $this->attendance->id]));

        $adminResponse->assertStatus(200);
        $adminResponse->assertSee($postData['shift_start']);
        $adminResponse->assertSee($postData['shift_end']);
        $adminResponse->assertSee($postData['note']);
        foreach ($postData['breaktimes'] as $breaktime) {
            $adminResponse->assertSee($breaktime['start_time']);
            $adminResponse->assertSee($breaktime['end_time']);
        }


        // 4. 申請一覧画面にアクセス（管理者）
        $listUser = $adminUser;
        $listResponse = $this->actingAs($listUser)->get(route('request'));
        $listResponse->assertStatus(200);

        // 5. 一覧画面に申請内容が表示されていることを検証
        // 申請一覧画面に申請内容が表示されていることを検証
        $listResponse->assertSee($postData['note']);
        $listResponse->assertSee('申請待ち');

        // 申請レコードから対象日を取得して検証
        $attendanceRequest = AttendanceRequest::where('attendance_id', $this->attendance->id)->first();
        $this->assertNotNull($attendanceRequest);
        $listResponse->assertSee(\Carbon\Carbon::parse($attendanceRequest->work_date)->format('Y/m/d'));


    }


        //「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_user_sees_all_their_pending_requests()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
        ]);

        $postData = [
            'shift_start' => '10:00',
            'shift_end'   => '19:00',
            'breaktimes'  => [
                ['start_time' => '12:00', 'end_time' => '13:00'],
            ],
            'note'        => '勤務時間修正のテスト申請',
        ];

        $this->post(route('attendance.edit', ['id' => $attendance->id]), $postData)->assertStatus(302);

        $response = $this->get(route('request', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSeeText('申請待ち');
        $response->assertSeeText($user->name);
        $response->assertSeeText($postData['note']);
        $response->assertSeeText(\Carbon\Carbon::parse($attendance->work_date)->format('Y/m/d'));
    }



    //「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_admin_sees_all_approved_requests_in_approved_tab()
    {
        $user = $this->user;
        $adminUser = User::where('role', 'admin')->first();

        // (1) ユーザーの勤怠データを3件取得、足りなければ作成
        $attendances = Attendance::where('user_id', $user->id)->take(3)->get();
        if ($attendances->count() < 3) {
            $missing = 3 - $attendances->count();
            $newAttendances = Attendance::factory()->count($missing)->create([
                'user_id' => $user->id,
                'work_date' => now()->subDays(rand(1,30))->format('Y-m-d'),
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
            ]);
            $attendances = $attendances->concat($newAttendances)->values();
        }

        // (2) 勤怠ごとに修正申請をPOST（ユーザーとして）
        $postDataList = [];
        foreach ($attendances as $attendance) {
            $postData = [
                'shift_start' => '10:00',
                'shift_end' => '19:00',
                'breaktimes' => [['start_time' => '12:00', 'end_time' => '13:00']],
                'note' => 'テスト勤務修正申請 ' . $attendance->work_date,
            ];
            $postDataList[] = $postData;

            $response = $this->actingAs($user)
                ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);
            $response->assertStatus(302);
        }

        // (3) 管理者の承認画面アクセス＆承認処理
        foreach ($attendances as $attendance) {
            // 今回は「勤怠ID」でURLを生成
            $attendanceId = $attendance->id;

            // 承認画面にアクセス（勤怠IDをパラメータに）
            $approveFormResponse = $this->actingAs($adminUser)
                ->get(route('admin.request.approveForm', ['attendance_correct_request' => $attendanceId]));
            $approveFormResponse->assertStatus(200);

            // 画面に何か申請のnoteなど表示されているか確認（AttendanceRequestから取得）
            $attendanceRequest = AttendanceRequest::where('attendance_id', $attendanceId)->first();
            $this->assertNotNull($attendanceRequest);
            $approveFormResponse->assertSeeText($attendanceRequest->note);

            // 承認処理（POST）も勤怠IDをパラメータに
            $approveResponse = $this->actingAs($adminUser)
                ->post(route('admin.request.update', ['attendance_correct_request' => $attendanceId]), [
                    'request_status' => 'approved',
                ]);
            $approveResponse->assertStatus(302);
    }



        // (4) 管理者で「承認済み」申請一覧画面にアクセス
        $listResponse = $this->actingAs($adminUser)
            ->get(route('request', ['status' => 'approved']));
        $listResponse->assertStatus(200);

        // (5) 申請一覧に全ての承認済み申請のnoteが表示されていることを検証
        foreach ($postDataList as $postData) {
            $listResponse->assertSeeText($postData['note']);
        }

        // 「申請済み」の文字も検証
        $listResponse->assertSeeText('申請済み');

        // ユーザー名も検証
        $listResponse->assertSeeText($user->name);
    }

    //各申請の「詳細」を押下すると申請詳細画面に遷移する
    public function test_user_can_access_request_detail_from_request_list_without_domcrawler()
    {
        $user = $this->user;

        // 勤怠レコード1件用意
        $attendance = Attendance::where('user_id', $user->id)->first();
        if (!$attendance) {
            $attendance = Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => now()->format('Y-m-d'),
                'shift_start' => '09:00:00',
                'shift_end' => '18:00:00',
            ]);
        }

        // 修正申請POST
        $postData = [
            'shift_start' => '10:00',
            'shift_end' => '19:00',
            'breaktimes' => [
                ['start_time' => '12:00', 'end_time' => '13:00']
            ],
            'note' => '詳細画面遷移テスト申請',
        ];

        $response = $this->actingAs($user)
            ->post(route('attendance.edit', ['id' => $attendance->id]), $postData);
        $response->assertStatus(302);

        // 申請一覧にアクセス
        $listResponse = $this->actingAs($user)
            ->get(route('request', ['status' => 'pending']));
        $listResponse->assertStatus(200);

        $html = $listResponse->getContent();

        // 例：「詳細」リンクのhrefを正規表現で検索
        // <a href="任意のURL" class="detail-link">詳細</a>
        // '詳細' + hrefがあるリンクを探す単純な正規表現例：
        if (preg_match('/<a\s+href="([^"]+)"[^>]*>詳細<\/a>/', $html, $matches)) {
            $detailUrl = $matches[1];


            // 詳細画面にアクセスしているか確認
            $detailResponse = $this->actingAs($user)->get($detailUrl);
            $detailResponse->assertStatus(200);

            // 申請理由が表示されていることを確認
            $detailResponse->assertSeeText('詳細画面遷移テスト申請');
        }
    }

}