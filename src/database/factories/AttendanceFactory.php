<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition()
    {
        // 勤務開始時刻（6時～16時の間でランダム）
        $startHour = $this->faker->numberBetween(6, 16);
        $workHours = $this->faker->numberBetween(8, 15); // 8～15時間勤務
        $start = now()->setTime($startHour, 0);
        $end = (clone $start)->addHours($workHours);

        // 休憩回数（0～4回）、1回あたり15～60分
        $breakCount = $this->faker->numberBetween(0, 4);
        $breakMinutes = $breakCount * $this->faker->numberBetween(15, 60);

        return [
            // user_id, work_dateはSeederで上書き
            'shift_start' => $start,
            'shift_end' => $end,
            'break_minutes' => $breakMinutes,
            'status' => 'after_work',
            'note' => null,
        ];
    }
}
