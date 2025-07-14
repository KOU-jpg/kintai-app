<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceRequestFormRequest;

class RequestController extends Controller
{
    public function store(AttendanceRequestFormRequest $request, Attendance $attendance)
{
    // 元の勤怠データを配列で取得
    $originalData = [
        'shift_start' => $attendance->shift_start,
        'shift_end' => $attendance->shift_end,
        'breaktimes' => $attendance->breaktimes->map(function ($break) {
            return [
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
            ];
        }),
        'note' => $attendance->note,
    ];

    // リクエストから新しい勤怠データを取得
    $requestedData = [
        'shift_start' => $request->input('shift_start'),
        'shift_end' => $request->input('shift_end'),
        'breaktimes' => collect($request->input('breaktimes', []))->filter(function ($bt) {
            // 空欄の休憩は除外
            return !empty($bt['start_time']) || !empty($bt['end_time']);
        })->values(),
        'note' => $request->input('note'),
    ];

    // 勤怠修正申請を保存
    AttendanceRequest::create([
        'attendance_id'   => $attendance->id,
        'user_id'         => Auth::id(),
        'original_data'   => json_encode($originalData),
        'requested_data'  => json_encode($requestedData),
        'status'          => 'pending',
    ]);

    return redirect()->route('request', $attendance->id);
}

public function request(Request $request)
{
    if (!$request->has('status')) {
        return redirect()->route('request', ['status' => 'pending']);
    }

    $status = $request->input('status', 'pending');

    // ログインユーザー自身のデータのみ取得
    $requests = AttendanceRequest::with(['user', 'attendance'])
        ->where('user_id', auth()->id())
        ->where('status', $status)
        ->orderBy('created_at', 'desc')
        ->get();

    return view('user.request', compact('requests', 'status'));
}
}
