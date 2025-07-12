<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $fillable = [
        'attendance_id',
        'attendance_request_id',
        'start_time',
        'end_time',
        'duration_minutes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
