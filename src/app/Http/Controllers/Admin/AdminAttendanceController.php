<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $month = request('month', now()->format('Y-m')); // "2026-02"
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
            ->keyBy('work_date'); // 'YYYY-MM-DD' => Attendance

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

    public function detail(Attendance $attendance)
    {
        $attendance->load(['user', 'breaks']);

        $workDate = $attendance->work_date;

        $break1 = $attendance->breaks->firstWhere('break_no', 1);
        $break2 = $attendance->breaks->firstWhere('break_no', 2);

        return view('admin.attendance.detail', compact('attendance', 'workDate', 'break1', 'break2'));
    }

    public function update(AdminUpdateRequest $request, Attendance $attendance)
    {
        $validated = $request->validated();

        $attendance->clock_in_at  = $validated['clock_in_at'] ?? null;
        $attendance->clock_out_at = $validated['clock_out_at'] ?? null;

        $attendance->note = $validated['note'];
        $attendance->save();

        foreach ([1, 2] as $no) {
            $start = data_get($validated, "breaks.$no.start"); // "H:i" or null
            $end   = data_get($validated, "breaks.$no.end");   // "H:i" or null

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
            ->with('message', '勤怠を更新しました');
    }

    private function mergeDateTime(string $date, ?string $time): ?string
    {
        if (!$time) return null;
        return Carbon::parse("$date $time")->format('Y-m-d H:i:s');
    }

    public function approveShow($id)
    {
        $req = AttendanceRequest::with(['user', 'attendance.breaks'])->findOrFail($id);

        $break1 = $req->attendance?->breaks->firstWhere('break_no', 1);
        $break2 = $req->attendance?->breaks->firstWhere('break_no', 2);

        $workDate = $req->attendance?->work_date;

        return view('admin.attendance.request-approve', compact('req'));
    }

    public function approve($id)
    {
        DB::transaction(function () use ($id) {
            $req = AttendanceRequest::with('attendance')->lockForUpdate()->findOrFail($id);

            if ($req->status === 'approved') return;

            // 出退勤だけ勤怠へ反映（休憩申請カラムが無いので breaks は触らない）
            $req->attendance->update([
                'clock_in_at'  => $req->request_clock_in_at,
                'clock_out_at' => $req->request_clock_out_at,
            ]);

            $req->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        });

        // 同じ画面に戻す → ボタンが「承認済み」へ
        return redirect()->route('admin.stamp_correction_request.approve.show', $id);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
