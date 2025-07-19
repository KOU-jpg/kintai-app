<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use stdClass;
use App\Http\Requests\AttendanceRequestFormRequest;
use App\Models\User;


class AdminController extends Controller
{
    public function indexAdmin(Request $request)
    {
        // 日付取得（なければ今日にリダイレクト）
        $date = $request->input('date');
        if (!$date) {
            $today = Carbon::today()->toDateString();
            return redirect()->route('admin.attendance.list', ['date' => $today]);
        }

        // 日付情報整形
        $carbonDate = Carbon::createFromFormat('Y-m-d', $date);
        $displayDate = $carbonDate->isoFormat('YYYY/M/D');
        $titleDate = $carbonDate->isoFormat('YYYY年M月D日');
        $prevDate = $carbonDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $carbonDate->copy()->addDay()->format('Y-m-d');

        // 出勤している人だけを取得（shift_start が NULL でない）
        $todayAttendances = Attendance::with('user')
            ->whereDate('work_date', $date)
            ->whereNotNull('shift_start')
            ->get();

        // 整形して $adminDatとしてBlade に渡す配列へ
        $adminData = $todayAttendances->map(function ($attendance) {
            $startTime = Carbon::parse($attendance->shift_start)->format('H:i');
            $endTime = $attendance->shift_end ? Carbon::parse($attendance->shift_end)->format('H:i') : '--:--';

            $breakMinutes = $attendance->break_minutes ?? 0;
            $breakHours = floor($breakMinutes / 60);
            $breakRemainMinutes = $breakMinutes % 60;
            $breakTime = sprintf('%02d:%02d', $breakHours, $breakRemainMinutes);

            $workTime = '--:--';
            if ($attendance->shift_start && $attendance->shift_end) {
                $start = Carbon::parse($attendance->shift_start);
                $end = Carbon::parse($attendance->shift_end);
                $totalMinutes = $end->diffInMinutes($start) - $breakMinutes;
                $workTime = sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
            }

            return [
                'user' => $attendance->user,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'break_time' => $breakTime,
                'work_time' => $workTime,
                'request_status' => $attendance->request_status, 
                'detail_url' => route('attendance.detailByRole', $attendance->id),
            ];
        });

        return view('admin.attendance_index', [
            'displayDate' => $displayDate,
            'titleDate' => $titleDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'adminData' => $adminData,
        ]);
    }



public function detailAdmin($id)
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
    return view('admin.attendance_detail', [
        'attendance' => (object)$attendanceData,
        'breaktimes' => $breaktimes,
    ]);
}



    public function createAdmin(Request $request)
    {
        $userId = $request->input('user_id');
        $date   = $request->input('date');

        $user = User::findOrFail($userId);

        return view('admin.attendance_create', [
            'user' => $user,
            'date' => $date,
        ]);
    }

}


