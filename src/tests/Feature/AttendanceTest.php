<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // 現在日時が画面に正しく表示されることを確認する
    public function test_current_datetime_is_displayed_correctly()
    {
        Carbon::setTestNow('2026-02-27 10:15:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年2月27日');
        $response->assertSee('10:15');

        Carbon::setTestNow();
    }

    // 勤怠記録がない場合、ステータスが「勤務外」になることを確認する
    public function test_status_is_before_when_no_attendance_record()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    // 出勤中はステータスが「出勤中」になることを確認する
    public function test_status_is_working()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    // 休憩中はステータスが「休憩中」になることを確認する
    public function test_status_is_breaking()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00:00',
            'status' => 'breaking',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    // 退勤済のときはステータスが「退勤済」になることを確認する
    public function test_status_is_after()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
    }

    // 出勤前は出勤ボタンが表示されることを確認する
    public function test_start_button_is_visible_when_before()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('出勤');
    }

    // 退勤済のとき、出勤ボタンが表示されないことを確認する
    public function test_start_button_is_hidden_when_status_is_after()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertDontSee('>出勤<', false);
    }

    // 出勤処理後、勤怠一覧画面に出勤時刻が正確に記録されていることを確認する
    public function test_clock_in_time_is_shown_on_attendance_list_after_start()
    {
        Carbon::setTestNow('2026-02-27 09:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        $response = $this->post('/attendance/start');
        $response->assertRedirect('/attendance');

        $list = $this->get('/attendance');

        $list->assertSee('09:00');

        Carbon::setTestNow();
    }

    // 出勤中のとき休憩入ボタンが表示され、休憩処理後にステータスが「休憩中」になることを確認する
    public function test_user_can_start_break()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $response = $this->post('/attendance/break/start');
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'breaking',
        ]);

        $this->get('/attendance')->assertSee('休憩中');
    }

    // 休憩入と休憩戻を行った後、再度「休憩入」ボタンが表示されることを確認する
    public function test_user_can_take_break_multiple_times_in_a_day()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $this->post('/attendance/break/start')->assertRedirect('/attendance');
        $this->post('/attendance/break/end')->assertRedirect('/attendance');
        $this->get('/attendance')->assertSee('休憩入');
    }

    // 休憩戻ボタンが表示され、処理後にステータスが「出勤中」になることを確認する
    public function test_user_can_end_break()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $this->post('/attendance/break/start')->assertRedirect('/attendance');
        $this->get('/attendance')->assertSee('休憩戻');
        $this->post('/attendance/break/end')->assertRedirect('/attendance');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => today(),
            'status' => 'working',
        ]);
    }

    // 休憩入→休憩戻→再度休憩入を行った後、「休憩戻」ボタンが表示されることを確認する
    public function test_user_can_end_break_multiple_times()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $this->post('/attendance/break/start')->assertRedirect('/attendance');
        $this->post('/attendance/break/end')->assertRedirect('/attendance');
        $this->post('/attendance/break/start')->assertRedirect('/attendance');
        $this->get('/attendance')->assertSee('休憩戻');
    }

    // 休憩入と休憩戻の処理後、勤怠一覧画面に休憩時刻が正確に記録されていることを確認する
    public function test_break_time_is_shown_on_attendance_list()
    {
        Carbon::setTestNow('2026-02-27 09:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        Carbon::setTestNow('2026-02-27 12:00');
        $this->post('/attendance/break/start')->assertRedirect('/attendance');

        Carbon::setTestNow('2026-02-27 13:00');
        $this->post('/attendance/break/end')->assertRedirect('/attendance');

        $list = $this->get('/attendance/list');
        $list->assertSee('1:00');

        Carbon::setTestNow();
    }

    // 出勤中のとき退勤ボタンが表示され、退勤処理後にステータスが「退勤済」になることを確認する
    public function test_user_can_end_work()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => '09:00',
            'status' => 'working',
        ]);

        $this->actingAs($user);

        $this->get('/attendance')->assertSee('退勤');
        $this->post('/attendance/end')->assertRedirect('/attendance');
        $this->get('/attendance')->assertSee('退勤済');
    }

    // 出勤と退勤の処理後、勤怠一覧画面に退勤時刻が正確に記録されている
    public function test_clock_out_time_is_shown_on_attendance_list_after_end()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        Carbon::setTestNow('2026-02-27 09:00');
        $this->post('/attendance/start')->assertRedirect('/attendance');

        Carbon::setTestNow('2026-02-27 18:00');
        $this->post('/attendance/end')->assertRedirect('/attendance');

        $list = $this->get('/attendance/list');

        $list->assertSee('02/27');
        $list->assertSee('18:00');

        Carbon::setTestNow();
    }

    // 自分の勤怠情報が勤怠一覧画面に表示される
    public function test_my_attendance_is_shown_on_list()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-27',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2026-02');

        $response->assertSee('02/27');
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_current_month_is_displayed_on_attendance_list()
    {

        Carbon::setTestNow('2026-02-27 09:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertSee('2026/02');

        Carbon::setTestNow();
    }

    // 「前月」を押下したとき、前月の情報が表示される
    public function test_previous_month_is_displayed_when_clicking_prev()
    {
        Carbon::setTestNow('2026-03-02 09:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-20',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $this->get('/attendance/list')
            ->assertSee('2026/03');

        $response = $this->get('/attendance/list?month=2026-02');
        $response->assertSee('2026/02');
        $response->assertSee('02/20');

        Carbon::setTestNow();
    }

    // 「翌月」を押下したとき、翌月の情報が表示される
    public function test_next_month_is_displayed()
    {
        Carbon::setTestNow('2026-03-15 10:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-05',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $this->get('/attendance/list')
            ->assertSee('2026/03');

        $response = $this->get('/attendance/list?month=2026-04');

        $response->assertSee('2026/04');
        $response->assertSee('04/05');

        Carbon::setTestNow();
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_can_navigate_to_attendance_detail()
    {
        Carbon::setTestNow('2026-03-15 10:00');

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=2026-03');
        $response->assertSee('/attendance/detail/2026-03-10');
        $detail = $this->get('/attendance/detail/2026-03-10');
        $detail->assertStatus(200);

        Carbon::setTestNow();
    }

    // 勤怠詳細画面にログインユーザーの名前が表示される
    public function test_attendance_detail_shows_logged_in_user_name()
    {
        Carbon::setTestNow('2026-03-15 10:00');

        $user = User::factory()->create([
            'name' => 'テスト 太郎',
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/2026-03-10');
        $response->assertSee('テスト 太郎');

        Carbon::setTestNow();
    }

    // 勤怠詳細画面に選択した日付が表示される
    public function test_attendance_detail_shows_selected_date()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/2026-03-10');

        $response->assertSee('2026年');
        $response->assertSee('3月10日');
    }

    // 勤怠詳細画面に表示される出勤・退勤時刻が打刻と一致している
    public function test_attendance_detail_shows_correct_clock_times()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/2026-03-10');

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 勤怠詳細画面に表示される休憩時刻が打刻と一致している
    public function test_attendance_detail_shows_correct_break_time()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_no' => 1,
            'break_start_at' => '12:00',
            'break_end_at' => '13:00',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/detail/2026-03-10');

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    // 出勤時間が退勤時間より後の場合、バリデーションエラーが表示される
    public function test_clock_in_after_clock_out_shows_validation_error()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $response = $this->from('/attendance/detail/2026-03-10')
            ->followingRedirects()
            ->post('/attendance/detail/2026-03-10', [
                'clock_in_at'  => '18:00',
                'clock_out_at' => '09:00',
            ]);

        $response->assertSee('出勤時間が不適切な値です');
    }

    // 休憩開始時間が退勤時間より後の場合、バリデーションエラーになる
    public function test_request_update_fails_when_break_start_is_after_clock_out()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user, 'web');

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        $response = $this->from('/attendance/detail/2026-03-10')->post(
            route('attendance.request', ['date' => '2026-03-10']),
            [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'reason' => 'テスト',
                'breaks' => [
                    1 => ['start' => '18:30', 'end' => '18:40'],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            'breaks.1.start' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後の場合、バリデーションエラーになる
    public function test_request_update_fails_when_break_end_is_after_clock_out()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user, 'web');

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        $response = $this->from('/attendance/detail/2026-03-10')->post(
            route('attendance.request', ['date' => '2026-03-10']),
            [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'reason' => 'テスト',
                'breaks' => [
                    1 => ['start' => '17:30', 'end' => '18:30'],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            'breaks.1.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考が未入力の場合、バリデーションエラーになる
    public function test_request_update_fails_when_reason_is_missing()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user, 'web');

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        $response = $this->from('/attendance/detail/2026-03-10')->post(
            route('attendance.request', ['date' => '2026-03-10']),
            [
                'clock_in_at' => '09:00',
                'clock_out_at' => '18:00',
                'reason' => '',
                'breaks' => [
                    1 => ['start' => '', 'end' => ''],
                ],
            ]
        );

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }

    // 修正申請処理が実行され、管理者の申請一覧/承認画面に表示されることを確認する
    public function test_request_is_visible_in_admin_list_and_approve_show()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        $this->actingAs($user);
        $this->from('/attendance/detail/2026-03-10')->post(
            route('attendance.request', ['date' => '2026-03-10']),
            [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'reason' => 'テスト',
                'breaks' => [
                    1 => ['start' => '12:00', 'end' => '13:00'],
                ],
            ]
        );

        $req = AttendanceRequest::latest('id')->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $req->status);


        $this->actingAs($admin);

        $list = $this->get('/stamp_correction_request/list');
        $list->assertStatus(200);
        $list->assertSee('申請一覧');
        $list->assertSee('テスト');
        $list->assertSee('2026/03/10');

        $approveShow = $this->get(route('stamp_correction_request.approve.show', [
            'attendance_correct_request_id' => $req->id,
        ]));

        $approveShow->assertStatus(200);
        $approveShow->assertSee('テスト');
    }

    // 承認待ち一覧にログインユーザーの申請が全て表示されること
    public function test_pending_request_list_shows_all_my_requests()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);


        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.request', ['date' => '2026-03-10']), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'reason' => 'テスト',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
        ])->assertRedirect();

        $list = $this->get('/stamp_correction_request/list');
        $list->assertStatus(200);

        $list->assertSee('テスト');
    }

    // 承認済み一覧にログインユーザーの申請が表示されること
    public function test_approved_requests_are_shown_on_user_request_list()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-03-10',
            'status'    => 'after',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.request', ['date' => '2026-03-10']), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'reason' => 'テスト',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
        ])->assertRedirect();

        $req = AttendanceRequest::latest('id')->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $req->status);

        $this->actingAs($admin);

        $this->from('/stamp_correction_request/approve/'.$req->id)
            ->post(route('stamp_correction_request.approve', [
                'attendance_correct_request_id' => $req->id,
        ]), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
            'reason' => 'テスト',
        ])->assertRedirect();

        $req->refresh();
        $this->assertSame('approved', $req->status);

        $this->actingAs($user);

        $list = $this->get('/stamp_correction_request/list?tab=approved');
        $list->assertStatus(200);
        $list->assertSee('テスト');
    }

    // 申請一覧の「詳細」から該当日の勤怠詳細画面に遷移できること
    public function test_request_list_detail_link_navigates_to_attendance_detail()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-03-10',
            'status'    => 'after',
        ]);

        $this->actingAs($user);

        $this->post(route('attendance.request', ['date' => '2026-03-10']), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'reason' => 'テスト',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
        ])->assertRedirect();

        $list = $this->get('/stamp_correction_request/list');
        $list->assertStatus(200);

        $list->assertSee('/attendance/detail/2026-03-10');

        $detail = $this->get('/attendance/detail/2026-03-10');
        $detail->assertStatus(200);
    }

    // 管理者が修正申請の詳細画面で「承認」ボタンを押下した場合、申請が承認され勤怠情報が更新されること
    public function test_admin_can_approve_request_and_attendance_is_updated()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => '2026-03-10',
            'status'       => 'after',
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
        ]);

        BreakTime::create([
            'attendance_id'  => $attendance->id,
            'break_no'       => 1,
            'break_start_at' => '12:00',
            'break_end_at'   => '13:00',
        ]);

        $this->actingAs($user);

        $this->from('/attendance/detail/2026-03-10')
            ->post(route('attendance.request', ['date' => '2026-03-10']), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'reason'       => 'テスト',
                'breaks' => [
                    1 => ['start' => '12:00', 'end' => '13:00'],
                ],
            ])->assertRedirect();

        $req = AttendanceRequest::latest('id')->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $req->status);

        $this->actingAs($admin);

        $this->from('/stamp_correction_request/approve/'.$req->id)
            ->post(route('stamp_correction_request.approve', [
                'attendance_correct_request_id' => $req->id,
            ]), [
                'clock_in_at'  => '08:00',
                'clock_out_at' => '17:00',
                'breaks' => [
                    1 => ['start' => '11:00', 'end' => '12:00'],
                ],
                'reason' => 'テスト',
            ])->assertRedirect();

        $req->refresh();
        $this->assertSame('approved', $req->status);
        $this->assertNotNull($req->approved_at);

        $attendance->refresh();
        $this->assertSame('08:00', $attendance->clock_in_at);
        $this->assertSame('17:00', $attendance->clock_out_at);

        $break1 = BreakTime::where('attendance_id', $attendance->id)
            ->where('break_no', 1)
            ->first();

        $this->assertNotNull($break1);
        $this->assertSame('11:00', $break1->break_start_at);
        $this->assertSame('12:00', $break1->break_end_at);
    }
}