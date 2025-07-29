<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'work_date',
        'shift_start',
        'shift_end',
        'break_start_time',
        'break_time', 
        'break_minutes',
        'total_work_minutes',
        'duration_minutes',
        'note',
        'request_status',
    ];
    
    protected $casts = [
        'break_time' => 'array',
        'shift_start' => 'datetime',
        'shift_end' => 'datetime',
        'work_date' => 'date',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

