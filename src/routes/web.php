<?php

use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.index');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/attendance/break/start', [AttendanceController::class, 'breakStart'])->name('attendance.break.start');
    Route::post('/attendance/break/end',   [AttendanceController::class, 'breakEnd'])->name('attendance.break.end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{date}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{date}', [AttendanceController::class, 'requestUpdate'])
        ->name('attendance.request');
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList'])
        ->name('stamp.request.list');
});

Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->middleware('guest')->name('admin.login');


Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {

        Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])
            ->name('attendance.list');

        Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])
            ->name('staff.list');

        Route::get('/attendance/staff/{user}', [AdminAttendanceController::class, 'staffMonthly'])
            ->name('attendance.staff');

        Route::get('/attendance/staff/{user}/csv', [AdminAttendanceController::class, 'staffMonthlyCsv'])
            ->name('attendance.staff.csv');

        Route::get('/attendance/{user}/{date}', [AdminAttendanceController::class, 'detail'])
            ->name('attendance.detail');

        Route::post('/attendance/{user}/{date}', [AdminAttendanceController::class, 'upsert'])
            ->name('attendance.upsert');

        Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

        Route::post('/logout', [AdminAttendanceController::class, 'logout'])
            ->name('logout');
    });

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminAttendanceController::class, 'approveShow'])
        ->name('stamp_correction_request.approve.show');

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminAttendanceController::class, 'approve'])
        ->name('stamp_correction_request.approve');
});
