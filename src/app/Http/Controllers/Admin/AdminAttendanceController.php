<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : today()->toDateString();

        $target = Carbon::parse($date);

        $prevDate = $target->copy()->subDay()->toDateString();
        $nextDate = $target->copy()->addDay()->toDateString();

        $users = User::where('role', 'user')
            ->with([
                'attendances' => function ($q) use ($date) {
                    $q->where('work_date', $date)
                        ->with('breaks');
                }
            ])
            ->orderBy('id')
            ->get();

        $dateLabel  = $target->format('Y/m/d');
        $titleLabel = $target->format('Y年n月j日の勤怠');

        return view('admin.attendance.list', compact(
            'users',
            'date',
            'dateLabel',
            'titleLabel',
            'prevDate',
            'nextDate'
        ));
    }

    public function staffList()
    {
        $users = User::where('role', 'user')->orderBy('id')->get();

        return view('admin.attendance.staff-list', compact('users'));
    }

    public function monthly(User $user)
    {
        $month = request('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month);

        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $dates = collect();
        for ($date = $start->copy(); $date <= $end; $date->addDay()) {
            $dates->push($date->copy());
        }

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy('work_date');

        return view('admin.attendance.staff-monthly', compact(
            'user',
            'dates',
            'attendances',
            'current'
        ));
    }

    public function staffMonthly(User $user)
    {
        $month = request('month', now()->format('Y-m'));
        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $dates = collect();
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $dates->push($d->copy());
        }

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');

        $currentMonthLabel = $current->format('Y/m');

        return view('admin.attendance.staff-monthly', compact(
            'user',
            'dates',
            'attendances',
            'prevMonth',
            'nextMonth',
            'currentMonthLabel'
        ));
    }

    public function staffMonthlyCsv(Request $request, \App\Models\User $user): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            abort(400, 'Invalid month format');
        }

        $current = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        $dates = collect();
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $dates->push($d->copy());
        }

        $fileName = "{$user->name}_{$current->format('Y_m')}_勤怠.csv";

        return response()->streamDownload(function () use ($dates, $attendances) {
            $out = fopen('php://output', 'w');

            $this->csvPut($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($dates as $date) {
                $key = $date->toDateString();
                $a = $attendances->get($key);

                $clockIn  = $a?->clock_in_at ? Carbon::parse($a->clock_in_at)->format('H:i') : '';
                $clockOut = $a?->clock_out_at ? Carbon::parse($a->clock_out_at)->format('H:i') : '';

                $breakMinutes = 0;
                if ($a) {
                    foreach ($a->breaks as $b) {
                        if ($b->break_start_at && $b->break_end_at) {
                            $breakMinutes += Carbon::parse($b->break_start_at)
                                ->diffInMinutes(Carbon::parse($b->break_end_at));
                        }
                    }
                }
                $breakStr = $breakMinutes ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '';

                $workStr = '';
                if ($a?->clock_in_at && $a?->clock_out_at) {
                    $workMinutes = Carbon::parse($a->clock_in_at)->diffInMinutes(Carbon::parse($a->clock_out_at));
                    $workMinutes = max(0, $workMinutes - $breakMinutes);
                    $workStr = sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60);
                }

                $this->csvPut($out, [
                    $date->format('m/d(D)'),
                    $clockIn,
                    $clockOut,
                    $breakStr,
                    $workStr,
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
        ]);
    }

    private function csvPut($handle, array $row): void
    {
        $converted = array_map(fn($v) => mb_convert_encoding((string)$v, 'SJIS-win', 'UTF-8'), $row);
        fputcsv($handle, $converted);
    }

    public function detail(User $user, string $date)
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $targetDate)
            ->with('breaks')
            ->first();

        $isPending = false;

        if ($attendance) {
            $isPending = AttendanceRequest::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->exists();
        }

        return view('admin.attendance.detail', compact(
            'user',
            'attendance',
            'targetDate',
            'isPending'
        ));
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        $validated = $request->validated();

        $attendance->clock_in_at  = $this->normalizeTime(data_get($validated, 'clock_in_at'));
        $attendance->clock_out_at = $this->normalizeTime(data_get($validated, 'clock_out_at'));

        $attendance->reason = $validated['reason'] ?? null;
        $attendance->save();

        foreach ([1, 2] as $no) {
            $start = $this->normalizeTime(data_get($validated, "breaks.$no.start"));
            $end   = $this->normalizeTime(data_get($validated, "breaks.$no.end"));

            if (!$start && !$end) {
                $attendance->breaks()->where('break_no', $no)->delete();
                continue;
            }

            $attendance->breaks()->updateOrCreate(
                ['break_no' => $no],
                [
                    'break_start_at' => $start,
                    'break_end_at'   => $end ?: null,
                ]
            );
        }

        return redirect()
            ->route('admin.attendance.detail', $attendance->id)
            ->with('message', '勤怠を更新しました。');
    }

    public function upsert(AttendanceUpdateRequest $request, User $user, string $date)
    {
        $validated = $request->validated();
        $date = Carbon::parse($date)->toDateString();

        $attendance = Attendance::firstOrNew([
            'user_id'   => $user->id,
            'work_date' => $date,
        ]);

        $attendance->clock_in_at  = $this->normalizeTime(data_get($validated, 'clock_in_at'));
        $attendance->clock_out_at = $this->normalizeTime(data_get($validated, 'clock_out_at'));
        $attendance->reason         = $validated['reason'] ?? null;

        $attendance->status = $attendance->clock_out_at
            ? 'after'
            : ($attendance->clock_in_at ? 'working' : 'before');

        $attendance->save();

        foreach ([1, 2] as $no) {
            $start = $this->normalizeTime(data_get($validated, "breaks.$no.start"));
            $end   = $this->normalizeTime(data_get($validated, "breaks.$no.end"));

            if (!$start && !$end) {
                $attendance->breaks()->where('break_no', $no)->delete();
                continue;
            }

            $attendance->breaks()->updateOrCreate(
                ['break_no' => $no],
                [
                    'break_start_at' => $start,
                    'break_end_at'   => $end ?: null,
                ]
            );
        }

        return redirect()
            ->route('admin.attendance.detail', ['user' => $user->id, 'date' => $date])
            ->with('message', '勤怠を更新しました。');
    }

    private function normalizeTime(?string $time): ?string
    {
        if (!$time) return null;
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    public function approveShow($id)
    {
        $req = AttendanceRequest::with(['user', 'attendance', 'requestBreaks'])
            ->findOrFail($id);

        $break1 = $req->requestBreaks->firstWhere('break_no', 1);
        $break2 = $req->requestBreaks->firstWhere('break_no', 2);

        $workDate = $req->attendance?->work_date;

        return view('admin.attendance.request-approve', compact('req', 'break1', 'break2', 'workDate'));
    }

    public function approve(AttendanceUpdateRequest $request, $attendance_correct_request_id)
{
    $validated = $request->validated();

    $req = AttendanceRequest::with(['attendance', 'requestBreaks'])
        ->findOrFail($attendance_correct_request_id);

    DB::transaction(function () use ($req, $validated) {
        $attendance = $req->attendance;

        $attendance->clock_in_at  = $validated['clock_in_at'] ?? null;
        $attendance->clock_out_at = $validated['clock_out_at'] ?? null;

        foreach ([1, 2] as $no) {
            $bs = data_get($validated, "breaks.$no.start");
            $be = data_get($validated, "breaks.$no.end");

            if (!$bs && !$be) {
                $attendance->breaks()->where('break_no', $no)->delete();
                continue;
            }

            $attendance->breaks()->updateOrCreate(
                ['break_no' => $no],
                ['break_start_at' => $bs, 'break_end_at' => $be]
            );
        }

        $attendance->status = $attendance->clock_out_at
            ? 'after'
            : ($attendance->clock_in_at ? 'working' : 'before');

        $attendance->save();

        $req->status = 'approved';
        $req->approved_at = now();
        $req->save();
    });

    return back()->with('message', '勤怠を更新しました');
}

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
