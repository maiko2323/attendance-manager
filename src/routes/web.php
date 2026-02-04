<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
});

Route::get('/', function () {
    return redirect()->route('attendance.index');
});