<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function pendingRequest()
    {
        return $this->hasOne(AttendanceRequest::class)
            ->where('status', 'pending');
    }
}
