<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequestFormRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        return [
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'breaktimes.*.start_time' => 'required_with:breaktimes.*.end_time|nullable|date_format:H:i|after:shift_start',
            'breaktimes.*.end_time'   => 'required_with:breaktimes.*.start_time|nullable|date_format:H:i|after:breaktimes.*.start_time|before:shift_end',
            'note' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'shift_start.required' => '出勤時刻を入力してください',
            'shift_end.required' => '退勤時刻を入力してください',
            'note.required' => '備考を記入してください',
            'shift_end.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'start_time.after' => '休憩時間が勤務時間外です',
            'end_time.before' => '休憩時間が勤務時間外です',
            'breaktimes.*.end_time.before' => '休憩終了は退勤時刻より前にしてください',
            'breaktimes.*.start_time.required_with' => '休憩開始と終了は両方入力するか、両方空欄にしてください',
            'breaktimes.*.end_time.required_with'   => '休憩開始と終了は両方入力するか、両方空欄にしてください',
        ];
    }

    public function attributes()
    {
        $attributes = [
            'shift_start' => '出勤時刻',
            'shift_end' => '退勤時刻',
            'note' => '備考',
        ];

        // 休憩は最大5件分
        for ($i = 0; $i < 5; $i++) {
            $num = $i + 1;
            $attributes["breaktimes.$i.start_time"] = "休憩{$num}の開始時刻";
            $attributes["breaktimes.$i.end_time"]   = "休憩{$num}の終了時刻";
        }

        return $attributes;
    }

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $shiftStart = $this->input('shift_start');
        $shiftEnd = $this->input('shift_end');
        $breaktimes = $this->input('breaktimes', []);

        if ($shiftStart && $shiftEnd) {
            $start = \Carbon\Carbon::createFromFormat('H:i', $shiftStart);
            $end = \Carbon\Carbon::createFromFormat('H:i', $shiftEnd);
            $workMinutes = $end->diffInMinutes($start);

            // 休憩合計（分）
            $totalBreak = 0;
            $breakPeriods = [];
            foreach ($breaktimes as $idx => $break) {
                if (!empty($break['start_time']) && !empty($break['end_time'])) {
                    $bStart = \Carbon\Carbon::createFromFormat('H:i', $break['start_time']);
                    $bEnd = \Carbon\Carbon::createFromFormat('H:i', $break['end_time']);
                    $totalBreak += $bEnd->diffInMinutes($bStart);

                    // 休憩時間帯を配列に保存
                    $breakPeriods[] = [
                        'index' => $idx,
                        'start' => $bStart,
                        'end' => $bEnd,
                    ];
                }
            }

            // 休憩時間同士が重複していないかチェック
            $count = count($breakPeriods);
            $hasOverlap = false;
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $breakPeriods[$i];
                    $b = $breakPeriods[$j];
                    // 期間が重なっているか判定
                    if ($a['start']->lt($b['end']) && $b['start']->lt($a['end'])) {
                        $hasOverlap = true;
                        break 2; // 二重ループを一気に抜ける
                    }
                }
            }
            if ($hasOverlap) {
                $validator->errors()->add('breaktimes', '休憩時間が重複している部分があります');
            }

        }
    });
}

}
