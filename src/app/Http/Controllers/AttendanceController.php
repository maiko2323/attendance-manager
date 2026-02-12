<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\RequestBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AttendanceController extends Controller
{
    public function index()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', today())
            ->first();

        $state = $attendance?->status ?? 'before';

        return view('attendance.index', compact('state'));
    }

    public function start(Request $request)
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', today())
            ->first();

        if (!$attendance) {
            Attendance::create([
                'user_id'     => Auth::id(),
                'work_date'   => today(),
                'clock_in_at' => now()->format('H:i:s'),
                'status'      => 'working',
            ]);
        }

        return redirect()->route('attendance.index');
    }

    public function end()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', today())
            ->firstOrFail();

        if ($attendance->status !== 'working') {
            return redirect()->route('attendance.index');
        }

        $attendance->update([
            'clock_out_at' => now()->format('H:i:s'),
            'status' => 'after',
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakStart(Request $request)
    {
        $userId = Auth::id();

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', today())
            ->firstOrFail();

        // 出勤中以外は休憩入できない
        if ($attendance->status !== 'working') {
            return back();
        }

        DB::transaction(function () use ($attendance) {
            $nextNo = BreakTime::where('attendance_id', $attendance->id)->max('break_no');
            $nextNo = is_null($nextNo) ? 1 : $nextNo + 1;

            BreakTime::create([
                'attendance_id'   => $attendance->id,
                'break_no'        => $nextNo,
                'break_start_at'  => now()->format('H:i:s'),
                'break_end_at'    => null,
            ]);

        $attendance->update(['status' => 'breaking']);
        });

        return redirect()->route('attendance.index');
    }

    public function breakEnd(Request $request)
    {
        $userId = Auth::id();

        $attendance = Attendance::where('user_id', $userId)
            ->where('work_date', today())
            ->firstOrFail();

        if ($attendance->status !== 'breaking') {
            return back();
        }

        DB::transaction(function () use ($attendance) {
            $break = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end_at')
                ->orderByDesc('break_no')
                ->firstOrFail();

            $break->update([
                'break_end_at' => now()->format('H:i:s'),
            ]);

            $attendance->update(['status' => 'working']);
        });

        return redirect()->route('attendance.index');
    }

    public function list(Request $request)
    {
        $month = $request->query('month');

        $current = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $start = $current->copy()->startOfMonth();
        $end   = $current->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy('work_date');

        $dates = \Carbon\CarbonPeriod::create($start, $end);

        $prevMonth = $current->copy()->subMonth()->format('Y-m');
        $nextMonth = $current->copy()->addMonth()->format('Y-m');
        $currentMonthLabel = $current->format('Y/m');

        return view('attendance.list', compact(
            'dates',
            'attendances',
            'prevMonth',
            'nextMonth',
            'currentMonthLabel',
            'month'
        ));
    }

    public function detail(string $date)
    {
        $workDate = \Carbon\Carbon::parse($date)->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->with('breaks')
            ->first();

        $pendingRequest = null;
        $isPending = false;

        if ($attendance) {
            $pendingRequest = AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->with('requestBreaks')
            ->latest()
            ->first();

            $isPending = (bool) $pendingRequest;
        }

        return view('attendance.detail', compact('workDate', 'attendance', 'isPending', 'pendingRequest'));
    }


    public function requestUpdate(Request $request, string $date)
    {
        $workDate = \Carbon\Carbon::parse($date)->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
            ->where('work_date', $workDate)
            ->first();

    // 休みの日は attendance を作る
        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id'   => Auth::id(),
                'work_date' => $workDate,
                'status'    => 'before',
            ]);
        }

    // 既に申請中なら戻す
        $already = AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($already) {
            return redirect()->route('attendance.detail', ['date' => $workDate]);
        }

        $data = $request->validate([
            'clock_in_at'  => ['nullable', 'date_format:H:i'],
            'clock_out_at' => ['nullable', 'date_format:H:i'],
            'reason'       => ['required', 'string'],

            'breaks'         => ['array'],
            'breaks.1.start' => ['nullable', 'date_format:H:i'],
            'breaks.1.end'   => ['nullable', 'date_format:H:i'],
            'breaks.2.start' => ['nullable', 'date_format:H:i'],
            'breaks.2.end'   => ['nullable', 'date_format:H:i'],
        ]);

        DB::transaction(function () use ($attendance, $data) {

        // ① 申請作成
            $requestRecord = AttendanceRequest::create([
                'attendance_id'        => $attendance->id,
                'user_id'              => Auth::id(),
                'request_clock_in_at'  => $data['clock_in_at'] ?? null,
                'request_clock_out_at' => $data['clock_out_at'] ?? null,
                'reason'               => $data['reason'],
                'status'               => 'pending',
                'approved_at'          => null,
            ]);

        // ② 休憩1・2（最大2回）
            foreach ([1, 2] as $no) {
                $start = $data['breaks'][$no]['start'] ?? null;
                $end   = $data['breaks'][$no]['end'] ?? null;

                if (!$start && !$end) {
                    continue;
                }

                RequestBreak::create([
                    'attendance_request_id' => $requestRecord->id,
                    'break_no'              => $no,
                    'break_start_at'        => $start,
                    'break_end_at'          => $end,
                ]);
            }
        });

        return redirect()->route('attendance.detail', ['date' => $workDate]);
    }

    public function stampCorrectionRequestList(Request $request)
    {
        // tab = pending | approved
        $tab = $request->query('tab', 'pending');

        $query = AttendanceRequest::where('user_id', Auth::id())
            ->with('attendance')
            ->orderByDesc('created_at');

        if ($tab === 'approved') {
            $query->where('status', 'approved');
        } else {
            $query->where('status', 'pending');
            $tab = 'pending';
        }

        $requests = $query->get();

        return view('attendance.stamp_correction_request_list', compact('requests', 'tab'));
    }

}