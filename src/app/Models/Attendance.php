<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function getWorkMinutesAttribute(): int
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return 0;
        }

        $in  = Carbon::parse($this->clock_in_at);
        $out = Carbon::parse($this->clock_out_at);

        return (int) $in->diffInMinutes($out, true);
    }

    public function getBreakMinutesAttribute(): int
    {
        $minutes = 0;

        foreach ($this->breaks as $b) {
            if ($b->break_start_at && $b->break_end_at) {
                $s = Carbon::parse($b->break_start_at);
                $e = Carbon::parse($b->break_end_at);

                $minutes += (int) $s->diffInMinutes($e, true);
            }
        }

        return $minutes;
    }

    public function getNetMinutesAttribute(): int
    {
        return max(0, $this->work_minutes - $this->break_minutes);
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function getBreakLabelAttribute(): string
    {
        return $this->formatMinutes($this->break_minutes);
    }

    public function getNetLabelAttribute(): string
    {
        return $this->formatMinutes($this->net_minutes);
    }
}
