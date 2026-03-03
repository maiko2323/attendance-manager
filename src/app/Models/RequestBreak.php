<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestBreak extends Model
{
    protected $table = 'request_breaks';

    protected $fillable = [
        'attendance_request_id',
        'break_no',
        'break_start_at',
        'break_end_at',
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class, 'attendance_request_id');
    }
}
