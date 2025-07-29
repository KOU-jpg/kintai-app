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
use App\Models\User;


class RequestController extends Controller
{

public function request(Request $request)
{
    $status    = $request->query('status', 'pending');
    $user      = Auth::user();
    $userRole  = $user->role;

    $query = AttendanceRequest::with(['user', 'attendance'])
        ->where('request_status', $status)
        ->orderByDesc('created_at');

    if ($userRole === 'user') {
        // 一般ユーザーは自分の申請のみ取得
        $query->where('user_id', $user->id);
    }

    $requests = $query->get();

    return view('common.request', [
        'requests' => $requests,
        'status'   => $status,
    ]);
}



public function updateAttendance(AttendanceRequestFormRequest $request, $id)
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



public function storeAdmin(AttendanceRequestFormRequest $request)
{ 
    // 値の取得
    $userId     = $request->input('user_id');
    $workDate   = $request->input('work_date');
    $shiftStart = $request->input('shift_start');
    $shiftEnd   = $request->input('shift_end');
    $breaktimes = $request->input('breaktimes', []);
    $note       = $request->input('note');

    // 開始・終了を合体して日時に変換
    $shiftStartDateTime = Carbon::parse("$workDate $shiftStart");
    $shiftEndDateTime   = Carbon::parse("$workDate $shiftEnd");

    //  breaktimes が空でもエラーにならないように処理する
    $breakMinutes = 0;
    $validBreaktimes = array_filter($breaktimes, function ($bt) {
        return !empty($bt['start_time']) && !empty($bt['end_time']);
    });

    foreach ($validBreaktimes as &$break) {
        try {
            $start = Carbon::createFromFormat('H:i', $break['start_time']);
            $end   = Carbon::createFromFormat('H:i', $break['end_time']);

            $duration = $start->diffInMinutes($end);
            $break['duration_minutes'] = $duration;
            $breakMinutes += $duration;
        } catch (\Exception $e) {
            $break['duration_minutes'] = 0;
        }
    }
    unset($break); // 参照変数の unset 忘れずに

    // 総労働時間（休憩引いた）
    $durationMinutes    = $shiftEndDateTime->diffInMinutes($shiftStartDateTime);
    $totalWorkMinutes   = $durationMinutes - $breakMinutes;

    // トランザクションで保存
    DB::transaction(function () use (
        $userId, $workDate, $shiftStartDateTime, $shiftEndDateTime,
        $validBreaktimes, $breakMinutes, $totalWorkMinutes, $durationMinutes, $note
    ) {
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
            'break_time'          => $validBreaktimes, // ← JSON保存
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

    public function approveForm(Attendance $attendance_correct_request)
    {
        $attendance = $attendance_correct_request->load(['user', 'attendanceRequest']); // モデルに関連ロードだけすればOK

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




    public function request_update(Request $request, Attendance $attendance_correct_request)
    {
    $attendance = $attendance_correct_request;
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




    public function create(Request $request)
    {
        $userId = $request->input('user_id');
        $date   = $request->input('date');

        $user = User::findOrFail($userId);

        return view('common.create', [
            'user' => $user,
            'date' => $date,
        ]);
    }



public function detail($id)
{
    // 勤怠情報を取得（attendance_requestsとのリレーションも含む）
    $attendance = Attendance::with(['user', 'breakTimes', 'attendanceRequest'])->findOrFail($id);
    // request_status を取得
    $status = optional($attendance->attendanceRequest)->request_status;

    if ($status === 'pending') {

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

    } else {
        
        // 通常の attendance 情報からデータ展開
        $breaktimes = $attendance->breakTimes;
        $attendanceData = [
            'id' => $attendance->id,
            'user' => $attendance->user,
            'work_date' => $attendance->work_date,
            'shift_start' => $attendance->shift_start,
            'shift_end' => $attendance->shift_end,
            'note' => $attendance->note,
            'request_status' => $status,
        ];
    }

    // ビューに渡す
    return view('common.detail', [
        'attendance' => (object)$attendanceData,
        'breaktimes' => $breaktimes,
    ]);


}






}