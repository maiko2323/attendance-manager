<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestBreak extends Model
{
    protected $fillable = [
        'attendance_request_id',
        'break_no',
        'break_start_at',
        'break_end_at',
    ];

    public function attendanceRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
