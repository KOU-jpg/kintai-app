<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
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
              'status' => 'before_work',
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
      $attendance->status = 'working';
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
      $attendance->status = 'on_break';
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
          return redirect()->route('attendance')->with('message', '進行中の休憩がありません');
      }

      // 休憩終了時刻とduration_minutesをセット
      $breakTime->end_time = now();
      $breakTime->duration_minutes = $breakTime->start_time->diffInMinutes($breakTime->end_time);
      $breakTime->save();

      // 合計休憩時間を計算して保存
      $totalBreakMinutes = BreakTime::where('attendance_id', $attendance->id)
          ->sum('duration_minutes');

      $attendance->break_minutes = $totalBreakMinutes;
      $attendance->status = 'working';
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
      $attendance->status = 'after_work';

      $breakMinutes = $attendance->break_minutes ?? 0;
      $totalWorkMinutes = $attendance->shift_end->diffInMinutes($attendance->shift_start) - $breakMinutes;
      $attendance->total_work_minutes = $totalWorkMinutes;

      $attendance->save();

      return redirect()->route('attendance');
  }




//container_header用
  protected function getMonthInfo(?string $month)
  {
    // URLクエリから?month=YYYY-MMを取得。なければ今月
    if ($month) {
        $current = Carbon::createFromFormat('Y-m', $month);
    } else {
        $current = Carbon::now()->startOfMonth();
    }

    return [
        'current' => $current,
        'prevMonth' => $current->copy()->subMonth()->format('Y-m'),
        'nextMonth' => $current->copy()->addMonth()->format('Y-m'),
        'displayMonth' => $current->format('Y/m'),
    ];
  }

public function index(Request $request)
{
    // 月情報取得
    $month = $request->input('month');
    $monthInfo = $this->getMonthInfo($month);

    $currentMonth = $monthInfo['current'];
    $startOfMonth = $currentMonth->copy()->startOfMonth();
    $endOfMonth = $currentMonth->copy()->endOfMonth();

    // ログインユーザーID取得（例: Auth::id()）
    $userId = auth()->id();

    // 今月分の勤怠データをDBから取得
    $rawAttendances = Attendance::where('user_id', $userId)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get()
        ->keyBy(function($item) {
            return Carbon::parse($item->work_date)->format('Y/m/d');
        });

    // 月の日数分ループして配列を作成
    $attendances = [];
    for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
        $dateStr = $date->format('Y/m/d');
        $dateParam = $date->format('Y-m-d'); // クエリ用
        $weekday = ['日','月','火','水','木','金','土'][$date->dayOfWeek];

        if ($rawAttendances->has($dateStr)) {
            $row = $rawAttendances[$dateStr];
            $attendances[] = [
                'date' => $dateStr,
                'weekday' => $weekday,
                'start_time' => $row->shift_start ? Carbon::parse($row->shift_start)->format('H:i') : '--:--',
                'end_time' => $row->shift_end ? Carbon::parse($row->shift_end)->format('H:i') : '--:--',
                'break_time' => $row->break_minutes ? sprintf('%d:%02d', intdiv($row->break_minutes, 60), $row->break_minutes % 60) : '--:--',
                'work_time' => $this->calcWorkTime($row),
                'detail_url' => route('attendance.edit', $row->id),
            ];
        } else {
            $attendances[] = [
                'date' => $dateStr,
                'weekday' => $weekday,
                'start_time' => '--:--',
                'end_time' => '--:--',
                'break_time' => '--:--',
                'work_time' => '--:--',
                'detail_url' => route('attendance.create') . '?date=' . $dateParam,
            ];
        }
    }

    return view('user.attendance_index', [
        'attendances'   => $attendances,
        'displayMonth'  => $monthInfo['displayMonth'],
        'prevMonth'     => $monthInfo['prevMonth'],
        'nextMonth'     => $monthInfo['nextMonth'],
    ]);
}

protected function calcWorkTime($row)
{
    if (isset($row->total_work_minutes) && $row->total_work_minutes !== null) {
        $hours = intdiv($row->total_work_minutes, 60);
        $mins = $row->total_work_minutes % 60;
        return sprintf('%d:%02d', $hours, $mins);
    }
    return '--:--';
}

public function edit($id)
{
    // 勤怠データを取得（ユーザーと休憩時間も一緒に取得）
    $attendance = Attendance::with(['user', 'breaktimes'])->findOrFail($id);

    // 「そのユーザー」「その勤怠」に紐づく pending な attendance_request を検索
    $pendingRequest = AttendanceRequest::where('attendance_id', $attendance->id)
        ->where('user_id', $attendance->user_id)
        ->where('status', 'pending')
        ->first();

    if ($pendingRequest) {
        // requested_data を表示するためにビューに渡す
        return view('user.attendance_edit', [
            'attendance'     => $attendance,
            'breaktimes'     => $attendance->breaktimes,
            'requested_data' => json_decode($pendingRequest->requested_data, true),
            'pendingRequest' => $pendingRequest,
        ]);
    } else {
        // 通常通り
        return view('user.attendance_detail', [
            'attendance' => $attendance,
            'breaktimes' => $attendance->breaktimes,
        ]);
    }
}




}