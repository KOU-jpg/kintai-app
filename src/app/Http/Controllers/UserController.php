<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\BreakTime;

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
  
    public function attendanceView()
    {
      $userId = auth()->id();
        $today = now()->toDateString();

        // 今日の勤怠データを取得。なければ新規作成（初期値で）
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $userId,
                'work_date' => $today,
            ],
            [
                // デフォルト値
                'work_status' => 'before_work',
                'break_minutes' => 0,
                // 他に必要な初期値があればここに追加
            ]
        );

        return view('user.attendance_register', [
            'attendance' => $attendance,
            'now' => now(),
        ]);
    }
    
    public function clockIn(Request $request)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        // 今日の勤怠データを取得（必ず存在する前提）
        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->firstOrFail();

        // 出勤打刻
        $attendance->shift_start = now();
        $attendance->work_status = 'working';
        $attendance->save();

        return redirect()->route('attendance');
    }


    public function startBreak(Request $request)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        // 勤怠データは必ず存在する前提
        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->firstOrFail();

        // 休憩開始レコードを新規作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now(),
            'duration_minutes' => 0,
        ]);

        // 勤怠ステータスを休憩中に変更
        $attendance->work_status = 'on_break';
        $attendance->save();

        return redirect()->route('attendance');
    }

    public function endBreak(Request $request)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->firstOrFail();

        // 進行中の休憩レコードを取得
        $breakTime = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('end_time')
            ->first();

        if (!$breakTime) {
            return redirect()->route('attendance');
        }

        // 休憩終了時刻とduration_minutesをセット
        $breakTime->end_time = now();
        $breakTime->duration_minutes = $breakTime->start_time->diffInMinutes($breakTime->end_time);
        $breakTime->save();

        // 合計休憩時間を計算して保存
        $totalBreakMinutes = BreakTime::where('attendance_id', $attendance->id)
            ->sum('duration_minutes');

        $attendance->break_minutes = $totalBreakMinutes;
        $attendance->work_status = 'working';
        $attendance->save();

        return redirect()->route('attendance');
    }


    public function clockOut(Request $request)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', $today)
            ->first();

        $attendance->shift_end = now();
        $attendance->work_status = 'after_work';

        $breakMinutes = $attendance->break_minutes ?? 0;
        $totalWorkMinutes = $attendance->shift_end->diffInMinutes($attendance->shift_start) - $breakMinutes;
        $attendance->total_work_minutes = $totalWorkMinutes;

        $attendance->save();

        return redirect()->route('attendance');
    }



































}
