<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
}