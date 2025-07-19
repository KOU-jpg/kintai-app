<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StaffController;
use Illuminate\Http\Request;

// 一般ユーザー：会員登録・ログイン
Route::post('/register', [AuthController::class, 'register'])->name('register'); // 会員登録処理
Route::post('/login', [AuthController::class, 'login'])->name('login');       // ログイン処理
// 管理者：ログイン
Route::get('/admin/login', [AuthController::class, 'loginViewAdmin']); // 管理者ログイン画面
Route::post('/admin/login', [AuthController::class, 'loginAdmin'])->name('admin.login');       // 管理者ログイン処理

//ログアウト
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::middleware(['auth'])->group(function () {

    // 管理者ユーザー用ルート（roleがadminの場合のみ）
    Route::middleware(['role:admin'])->group(function () {

        // 管理者：勤怠一覧画面
        Route::get('/admin/attendance/list', [AdminController::class, 'indexAdmin'])->name('admin.attendance.list'); 

        // 管理者：スタッフ一覧
        Route::get('/admin/staff/list', [StaffController::class, 'staffList'])->name('admin.staff.list'); 

        // 管理者：スタッフ別勤怠一覧
        Route::get('/admin/attendance/staff/{id}', [StaffController::class, 'staffMonth'])->name('admin.attendance.staff'); 




        //SCV出力
        Route::post('/attendance/export-csv', [StaffController::class, 'exportCsv'])->name('attendance.exportCsv');

        // 管理者：修正申請承認
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [RequestController::class, 'approveForm'])->name('admin.request.approveForm'); // 承認画面
        Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [RequestController::class, 'request_update'])->name('admin.request.update'); // 承認処理

        // 管理者：申請一覧
        Route::get('/stamp_correction_request/list', [RequestController::class, 'showAdmin'])->name('admin.request');
    });

    // 管理者以外（またはroleチェックなし）の一般ユーザー用ルート
    // 勤怠登録画面・打刻系
    Route::get('/attendance', [AttendanceController::class, 'attendanceView'])->name('attendance'); // 勤怠登録画面表示
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn'); // 出勤
    Route::post('/attendance/start-break', [AttendanceController::class, 'startBreak'])->name('attendance.startBreak'); // 休憩開始
    Route::post('/attendance/end-break', [AttendanceController::class, 'endBreak'])->name('attendance.endBreak'); // 休憩終了
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut'); // 退勤

    // 勤怠一覧・詳細・新規作成
    Route::get('/attendance/list', [UserController::class, 'index'])->name('index'); // 勤怠一覧




    // 勤怠画面表示
    Route::get('/attendance/{id}', function ($id) {
        $userRole = Auth::user()->role;

        if ($userRole === 'admin') {
            return app(AdminController::class)->detailAdmin($id);
        }

        return app(UserController::class)->detailUser($id);
    })->name('attendance.detailByRole');

    Route::post('/attendance/{id}/edit', [RequestController::class, 'updateAdmin'])
    ->name('attendance.edit');

    // 勤怠データ新規作成
    Route::get('/admin/attendance/create', function (Request $request) {
        $userRole = Auth::user()->role;

        if ($userRole === 'admin') {
            return app(AdminController::class)->createAdmin($request);
        }

        return app(UserController::class)->createUser($request);
    })->name('attendance.create');


    Route::post('/admin/attendance/store', [RequestController::class, 'storeAdmin'])->name('attendance.store');
        
    // 申請一覧（一般ユーザー）
    Route::get('/stamp_correction_request/list', [RequestController::class, 'request'])->name('request');
});