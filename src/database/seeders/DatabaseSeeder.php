<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 管理者ユーザー作成（→ $adminに格納）
        $admin = User::create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // 一般ユーザー作成（→ $generalに格納）
        $general = User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // 一般ユーザーを50人ランダム作成
        $users = User::factory(5)->create();

        // 上から3人だけ勤怠データを2か月分作成
        $targetUsers = collect([$admin, $general])->concat($users->take(5));

        foreach ($targetUsers as $user) {
            foreach (range(0, 59) as $i) {
                $date = Carbon::now()->subDays(59 - $i);

                // 勤怠時間の設定
                $minWorkHours = 8;
                $maxWorkHours = 15;
                $workHours = rand($minWorkHours, $maxWorkHours);

                // 勤怠開始は0:00～(24-勤務時間)時の間でランダム
                $maxStartHour = 24 - $workHours;
                $startHour = rand(0, $maxStartHour);
                $shiftStart = $date->copy()->setTime($startHour, 0, 0);

                // 勤怠終了は同日中
                $shiftEnd = $shiftStart->copy()->addHours($workHours);
                if ($shiftEnd->format('Y-m-d') !== $shiftStart->format('Y-m-d')) {
                    // 万一日付をまたいだ場合は23:59に調整
                    $shiftEnd = $date->copy()->setTime(23, 59, 0);
                    $workHours = $shiftEnd->diffInHours($shiftStart);
                }

                // 休憩の設定
                $breakCount = rand(0, 4);
                $totalBreak = 0;
                $lastBreakEnd = $shiftStart->copy();
                $breakTimes = [];

                for ($j = 0; $j < $breakCount; $j++) {
                    // 休憩開始は前の休憩終了または出勤直後から30～120分後
                    $breakStart = $lastBreakEnd->copy()->addMinutes(rand(30, 120));
                    // 休憩終了は開始から15～60分後
                    $breakEnd = $breakStart->copy()->addMinutes(rand(15, 60));

                    // 休憩終了が退勤時刻や同日23:59を超えないように
                    if ($breakEnd > $shiftEnd || $breakEnd->format('Y-m-d') !== $date->format('Y-m-d')) {
                        break;
                    }

                    $duration = $breakEnd->diffInMinutes($breakStart);

                    $breakTimes[] = [
                        'start_time' => $breakStart,
                        'end_time' => $breakEnd,
                        'duration_minutes' => $duration,
                    ];

                    $totalBreak += $duration;
                    $lastBreakEnd = $breakEnd;
                }

                $totalWorkMinutes = $shiftEnd->diffInMinutes($shiftStart) - $totalBreak;
                if ($totalWorkMinutes < 0) $totalWorkMinutes = 0;

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date->format('Y-m-d'),
                    'shift_start' => $shiftStart,
                    'shift_end' => $shiftEnd,
                    'break_minutes' => $totalBreak,
                    'work_status' => 'after_work',
                    'note' => null,
                    'total_work_minutes' => $totalWorkMinutes,
                ]);

                // Attendance作成後にBreakTimeをinsert
                foreach ($breakTimes as $bt) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $bt['start_time'],
                        'end_time' => $bt['end_time'],
                        'duration_minutes' => $bt['duration_minutes'],
                    ]);
                }
            }
        }
    }
}
