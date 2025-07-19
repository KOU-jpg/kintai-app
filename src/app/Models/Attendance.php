<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceRequest;


class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'shift_start',
        'shift_end',
        'break_minutes',
        'work_status',
        'note',
        'total_work_minutes',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'shift_start' => 'datetime',
        'shift_end'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRequest()
    {
         return $this->hasOne(AttendanceRequest::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    
}
