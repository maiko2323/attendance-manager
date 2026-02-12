<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break.start');
    Route::post('/attendance/break/end',   [AttendanceController::class, 'breakEnd'])->name('attendance.break.end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{date}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{date}', [AttendanceController::class, 'requestUpdate'])
        ->name('attendance.request');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'stampCorrectionRequestList'])
        ->name('stamp_correction_request.list');


});

Route::get('/', function () {
    return redirect()->route('attendance.index');
});