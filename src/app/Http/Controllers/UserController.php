<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;


class UserController extends Controller
{

public function index(Request $request)
{
    $userId = auth()->id();
    $user = User::findOrFail($userId);

    $targetMonth = $request->input('month') ?? now()->format('Y-m');
    $date = Carbon::parse($targetMonth . '-01');

    $startOfMonth = $date->copy()->startOfMonth();
    $endOfMonth = $date->copy()->endOfMonth();

    $prevMonth = $startOfMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $startOfMonth->copy()->addMonth()->format('Y-m');
    $displayMonth = $startOfMonth->format('Y年n月');

    //  正しいカラム名で取得 & キー設定
    $attendancesRaw = Attendance::where('user_id', $userId)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get()
        ->keyBy(function ($item) {
            return Carbon::parse($item->work_date)->format('Y-m-d');
        });

    $attendances = [];

    for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
        $formattedDate = $date->format('Y-m-d');

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[$date->dayOfWeek];

        $attendance = $attendancesRaw->get($formattedDate);

        if ($attendance && optional($attendance->attendanceRequest)->request_status === 'pending') {
            // 勤怠はあるけど "申請中" の状態
            $attendances[] = [
                'date'       => $formattedDate,
                'weekday'    => $weekday,
                'start_time' => '申請中',
                'end_time'   => '申請中',
                'break_time' => '申請中',
                'work_time'  => '申請中',
                'detail_url' => route('attendance.detailByRole', ['id' => $attendance->id]),
            ];
        } else {
            // 通常の勤務データあり
            $attendances[] = [
                'date'       => $formattedDate,
                'weekday'    => $weekday,
                'start_time' => $attendance ? Carbon::parse($attendance->shift_start)->format('H:i') : '',
                'end_time'   => $attendance ? Carbon::parse($attendance->shift_end)->format('H:i') : '',
                'break_time' => $attendance ? gmdate('H:i', $attendance->break_minutes * 60) : '',
                'work_time'  => $attendance ? gmdate('H:i', $attendance->total_work_minutes * 60) : '',
                'detail_url' => $attendance
                            ? route('attendance.detailByRole', ['id' => $attendance->id])
                            : route('attendance.create', ['user_id' => $user->id, 'date' => $formattedDate]),
            ];
        }
    }

    return view('user.attendance_index', [
        'user' => $user,
        'attendances' => $attendances,
        'prevMonth' => $prevMonth,
        'nextMonth' => $nextMonth,
        'displayMonth' => $displayMonth,
    ]);
}
  



public function detailUser($id)
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
    return view('user.attendance_detail', [
        'attendance' => (object)$attendanceData,
        'breaktimes' => $breaktimes,
    ]);
}

    public function createUser(Request $request)
    {
        $userId = $request->input('user_id');
        $date   = $request->input('date');

        $user = User::findOrFail($userId);

        return view('user.attendance_create', [
            'user' => $user,
            'date' => $date,
        ]);
    }


































}
