<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Requests\AttendanceRequestFormRequest;
use Illuminate\Support\Facades\DB;


class RequestController extends Controller
{
public function request(Request $request)
{
    $status = $request->query('status', 'pending');

    // 申請データ取得（リレーション付き）
    $requests = AttendanceRequest::with(['user', 'attendance'])
        ->where('request_status', $status)
        ->orderByDesc('created_at')
        ->get();

    return view('admin.request_index', [
        'requests' => $requests, // 💡 ビューファイルでもこの変数名で使うように合わせよう！
        'status' => $status,
    ]);
}



public function updateAdmin(AttendanceRequestFormRequest $request, $id)
{
    $attendance = Attendance::with('attendanceRequest')->findOrFail($id);

    $shiftStart  = $request->input('shift_start');
    $shiftEnd    = $request->input('shift_end');
    $date        = Carbon::parse($request->input('work_date'))->format('Y-m-d'); 
    $note        = $request->input('note');

    // 👇 不正な休憩（空欄）を除外し、indexを詰める
    $rawBreaktimes = $request->input('breaktimes', []);
    $breaktimes = array_values(array_filter($rawBreaktimes, function ($break) {
        return !empty($break['start_time']) && !empty($break['end_time']);
    }));

    $breakMinutes = 0;
    foreach ($breaktimes as &$break) {
        try {
            $start = Carbon::createFromFormat('H:i', $break['start_time']);
            $end   = Carbon::createFromFormat('H:i', $break['end_time']);
            $duration = $start->diffInMinutes($end);
            $break['duration_minutes'] = $duration;
            $breakMinutes += $duration;
        } catch (\Exception $e) {
            $break['duration_minutes'] = 0; // ← 安全対策
        }
    }
    unset($break);

    $shiftStartDateTime = Carbon::parse("{$date} {$shiftStart}");
    $shiftEndDateTime   = Carbon::parse("{$date} {$shiftEnd}");
    $durationMinutes    = $shiftEndDateTime->diffInMinutes($shiftStartDateTime);
    $totalWorkMinutes   = $durationMinutes - $breakMinutes;

    DB::transaction(function () use (
        $attendance, $date, $shiftStartDateTime, $shiftEndDateTime,
        $breaktimes, $breakMinutes, $totalWorkMinutes, $durationMinutes, $note
    ) {
        AttendanceRequest::updateOrCreate(
            ['attendance_id' => $attendance->id],
            [
                'user_id'             => $attendance->user_id,
                'work_date'           => $date,
                'shift_start'         => $shiftStartDateTime,
                'shift_end'           => $shiftEndDateTime,
                'break_time'          => $breaktimes,
                'break_minutes'       => $breakMinutes,
                'total_work_minutes'  => $totalWorkMinutes,
                'duration_minutes'    => $durationMinutes,
                'note'                => $note,
                'request_status'      => 'pending',
            ]
        );
    });

    return redirect()->route('request', $attendance->id);
}



    //新規データ登録
    public function storeAdmin(AttendanceRequestFormRequest $request)
    { 
        // 値の取得
        $userId     = $request->input('user_id');
        $workDate   = $request->input('work_date');
        $shiftStart = $request->input('shift_start');
        $shiftEnd   = $request->input('shift_end');
        $breaktimes = $request->input('breaktimes', []);
        $note       = $request->input('note');
        $breakStart   = $breaktimes[0]['start_time'] ?? null;
        $breakEnd     = $breaktimes[0]['end_time']   ?? null;

        $shiftStartDateTime = Carbon::parse("$workDate $shiftStart");
        $shiftEndDateTime   = Carbon::parse("$workDate $shiftEnd");

        $breakMinutes = Carbon::createFromFormat('H:i', $breakEnd)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $breakStart));

        $durationMinutes    = $shiftEndDateTime->diffInMinutes($shiftStartDateTime);
        $totalWorkMinutes   = $durationMinutes - $breakMinutes;

            //  トランザクションで全体を安全に保存
        DB::transaction(function () use (
            $userId, $workDate, $shiftStartDateTime, $shiftEndDateTime,
            $breaktimes, $breakMinutes, $totalWorkMinutes, $durationMinutes, $note
        ) {
            // 勤怠はダミー登録（未承認なのでnull）
            $attendance = Attendance::create([
                'user_id'            => $userId,
                'work_date'          => $workDate,
                'shift_start'        => null,
                'shift_end'          => null,
                'break_minutes'      => 0,
                'total_work_minutes' => 0,
                'work_status'        => 'after_work',
                'note'               => null,
            ]);

            AttendanceRequest::create([
                'attendance_id'       => $attendance->id,
                'user_id'             => $userId,
                'work_date'           => $workDate,
                'shift_start'         => $shiftStartDateTime,
                'shift_end'           => $shiftEndDateTime,
                'break_time'          => $breaktimes, // ← JSONカラム
                'break_minutes'       => $breakMinutes,
                'total_work_minutes'  => $totalWorkMinutes,
                'duration_minutes'    => $durationMinutes,
                'note'                => $note,
                'request_status'      => 'pending',
            ]);
        });
        return redirect()->route('request');
    }

//リクエスト承認画面表示

    public function approveForm($id){
{
    // 勤怠情報を取得（attendance_requestsとのリレーションも含む）
    $attendance = Attendance::with(['user', 'attendanceRequest'])->findOrFail($id);

    // request_status を取得
    $status = optional($attendance->attendanceRequest)->request_status;



        // AttendanceRequest の値をビューに渡す用に整形
        $attendanceData = [
            'id' => $attendance->id,
            'user' => $attendance->user,
            'work_date' => $attendance->attendanceRequest->work_date,
            'shift_start' => $attendance->attendanceRequest->shift_start,
            'shift_end' => $attendance->attendanceRequest->shift_end,
            'note' => $attendance->attendanceRequest->note,
            'request_status' => $status,
        ];

        // break_time（JSON）を加工
        $breaktimes = collect();
        $jsonBreaks = $attendance->attendanceRequest->break_time;

        if (is_array($jsonBreaks)) {
            foreach ($jsonBreaks as $bt) {
                $breaktimes->push((object)[
                    'start_time' => $bt['start_time'] ?? null,
                    'end_time'   => $bt['end_time'] ?? null,
                ]);
            }
        }


    // ビューに渡す
    return view('admin.request_approve', [
        'attendance' => (object)$attendanceData,
        'breaktimes' => $breaktimes,
    ]);
}
    }



    public function request_update(Request $request, $id)
    {
        // 1. Attendance を取得（失敗時は404）
        $attendance = Attendance::findOrFail($id);

        // 2. AttendanceRequest を取得（1件のみを想定）
        // ※「1対1」関係であれば → $attendance->attendanceRequest を使ってもいい
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->firstOrFail();
        // 3. Attendance を AttendanceRequest の内容で上書き
        $attendance->shift_start         = $attendanceRequest->shift_start;
        $attendance->shift_end           = $attendanceRequest->shift_end;
        $attendance->break_minutes       = $attendanceRequest->break_minutes;
        $attendance->note                = $attendanceRequest->note;
        $attendance->total_work_minutes  = $attendanceRequest->total_work_minutes;
        $attendance->save();

        // 4. AttendanceRequest のステータスと備考を更新
        $attendanceRequest->request_status = 'approved';
        $attendanceRequest->save();

        // 5. 関連する BreakTime を削除
        BreakTime::where('attendance_id', $attendance->id)->delete();

        // 6. break_time 取得
        $breakTimes = $attendanceRequest->break_time;

        if (is_array($breakTimes)) {
            foreach ($breakTimes as $bt) {
                // duration_minutes が存在しない場合は計算する
                $durationMinutes = $bt['duration_minutes'] ?? null;

                if ($durationMinutes === null && !empty($bt['start_time']) && !empty($bt['end_time'])) {
                    try {
                        $start = \Carbon\Carbon::createFromFormat('H:i', $bt['start_time']);
                        $end   = \Carbon\Carbon::createFromFormat('H:i', $bt['end_time']);
                        $durationMinutes = $start->diffInMinutes($end);
                    } catch (\Exception $e) {
                        $durationMinutes = 0; // フォーマットが不正などのエラー時には0
                    }
                }

                BreakTime::create([
                    'attendance_id'    => $attendance->id,
                    'start_time'       => $bt['start_time'] ?? null,
                    'end_time'         => $bt['end_time']   ?? null,
                    'duration_minutes' => $durationMinutes,
                ]);
            }
        }

        // 7. リダイレクト
        return redirect()->route('request');



    }
}