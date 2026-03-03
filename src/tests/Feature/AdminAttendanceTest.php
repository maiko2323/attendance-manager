<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // 管理者がログインした場合、当日の全ユーザーの勤怠を確認できる
    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        Carbon::setTestNow('2026-03-10 09:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user1 = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト太郎',
        ]);

        $user2 = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト花子',
]       );

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);;

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '10:00',
            'clock_out_at' => '19:00',
            'status' => 'after',
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        $response->assertSee('テスト太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('テスト花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        Carbon::setTestNow();
    }

    // 管理者が勤怠一覧画面へ遷移した場合、当日の日付が表示される
    public function test_attendance_list_shows_current_date_when_opened()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->get(route('admin.attendance.list'));

        $response->assertStatus(200);
        $response->assertSee('2026/03/10');

        Carbon::setTestNow();
    }

    //管理者が「前日」を押した場合、前日の日付の勤怠情報が表示される
    public function test_attendance_list_can_show_previous_day()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->get(route('admin.attendance.list') . '?date=2026-03-09');
        $response->assertStatus(200);

        $response->assertSee('2026/03/09');

        Carbon::setTestNow();
    }

    //管理者が「翌日」を押した場合、翌日の日付の勤怠情報が表示される
    public function test_attendance_list_can_show_next_day()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->get(route('admin.attendance.list') . '?date=2026-03-11');
        $response->assertStatus(200);

        $response->assertSee('2026/03/11');

        Carbon::setTestNow();
    }

    // 管理者が勤怠詳細を開いたとき、選択した勤怠情報が表示されること
    public function test_admin_attendance_detail_shows_selected_data()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト 太郎',
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
            'break_end_at'   => '13:00',
        ]);

        $response = $this->get(route('admin.attendance.detail', [
            'user' => $user->id,
            'date' => '2026-03-10',
        ]));

        $response->assertStatus(200);

        $response->assertSee('テスト 太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');

        $response->assertSee('2026');
        $response->assertSee('3');
        $response->assertSee('10');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージ
    public function test_request_update_fails_when_clock_in_is_after_clock_out_for_admin()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin, 'web');

        $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
        ]);

        $req = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'request_clock_in_at' => '18:00',
            'request_clock_out_at' => '09:00',
            'reason' => 'reason text',
            'status' => 'pending',
        ]);

        $payload = [
        'clock_in_at'  => '18:00', // ★ 出勤が退勤より後
        'clock_out_at' => '17:00',
        'breaks' => [
            1 => ['start' => null, 'end' => null],
            2 => ['start' => null, 'end' => null],
        ],
        'reason' => 'reason text', // ★ required
    ];

    // ★ from() 追加：戻り先（referer）を固定して errors をセッションに載せる
    // ここは「実際に存在するURL」なら何でもOK（詳細URLが違うなら合わせて）
    $response = $this
        ->from('/admin/attendance/' . $attendance->id)
        ->post(
            route('stamp_correction_request.approve', ['attendance_correct_request_id' => $req->id]),
            $payload
        );

    $response->assertStatus(302);

    $response->assertSessionHasErrors([
        'clock_in_at' => '出勤時間が不適切な値です',
    ]);

    Carbon::setTestNow();
}

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージ
    public function test_request_update_fails_when_break_start_is_after_clock_out_for_admin()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin, 'web');

        $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
        ]);

        $req = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'request_clock_in_at' => '09:00',
            'request_clock_out_at' => '17:00',
            'reason' => 'reason text',
            'status' => 'pending',
        ]);

        $response = $this
            ->from('/admin/attendance/' . $attendance->id)
            ->post(
                route('stamp_correction_request.approve', ['attendance_correct_request_id' => $req->id]),
                [
                    'clock_in_at' => '09:00',
                    'clock_out_at' => '17:00',
                    'breaks' => [
                        1 => ['start' => '18:00', 'end' => '18:10'],
                        2 => ['start' => null, 'end' => null],
                    ],
                    'reason' => 'reason text',
                ]
            );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.1.start' => '休憩時間が不適切な値です',
        ]);

        Carbon::setTestNow();
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージ
    public function test_request_update_fails_when_break_end_is_after_clock_out_for_admin()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin, 'web');

        $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
        ]);

        $req = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'request_clock_in_at' => '09:00',
            'request_clock_out_at' => '17:00',
            'reason' => 'reason text',
            'status' => 'pending',
        ]);

        $response = $this->post(
            route('stamp_correction_request.approve', ['attendance_correct_request_id' => $req->id]),
            [
                'clock_in_at' => '09:00',
                'clock_out_at' => '17:00',
                'breaks' => [
                    1 => ['start' => '09:10', 'end' => '18:00'],
                ],
                'reason' => 'reason text',
            ]
        );

        $response->assertSessionHasErrors([
            'breaks.1.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        Carbon::setTestNow();
    }

    // 備考が未入力の場合になっている場合、エラーメッセージ
    public function test_request_update_fails_when_reason_is_missing_for_admin()
    {
        Carbon::setTestNow('2026-03-10 10:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin, 'web');

        $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
        ]);

        $req = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'request_clock_in_at' => '09:00',
            'request_clock_out_at' => '17:00',
            'reason' => 'some reason',
            'status' => 'pending',
        ]);

        $response = $this->post(
            route('stamp_correction_request.approve', ['attendance_correct_request_id' => $req->id]),
            [
                'clock_in_at' => '09:00',
                'clock_out_at' => '17:00',
                'breaks' => [
                    1 => ['start' => '', 'end' => ''],
                ],
                'reason' => '',
            ]
        );

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);

        Carbon::setTestNow();
    }

    // 管理者がスタッフ一覧画面へ遷移した場合、全ユーザーの名前とメールアドレスが表示される
    public function test_admin_can_see_all_users_on_staff_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user1 = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
            'email' => 'one@example.com',
        ]);
        $user2 = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト 花子',
            'email' => 'two@example.com',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        $response->assertSee('テスト 太郎');
        $response->assertSee('one@example.com');
        $response->assertSee('テスト 花子');
        $response->assertSee('two@example.com');
    }

    // 管理者がユーザーの勤怠一覧画面へ遷移した場合、そのユーザーの勤怠情報が表示される
    public function test_admin_can_see_selected_user_attendance_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

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
            'attendance_id'  => $attendance->id,
            'break_no'       => 1,
            'break_start_at' => '12:00',
            'break_end_at'   => '13:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', ['user' => $user->id]));
        $response->assertStatus(200);

        $response->assertSeeText('03/10');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertSeeText('1:00');
        $response->assertSeeText('8:00');

        $response->assertSee($user->name);
    }

    //管理者がユーザーの勤怠一覧画面へ遷移後、「前月」を押下した場合、前月の情報が表示される
    public function test_admin_can_see_previous_month_attendance_when_click_prev_month_button()
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-03'
        ]));

        $response->assertStatus(200);
        $response->assertSeeText('03/10');

        $response->assertSee(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-02'
        ]));

        $prevResponse = $this->get(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-02'
        ]));

        $prevResponse->assertStatus(200);
        $prevResponse->assertSeeText('02/10');
    }

    // 管理者がユーザーの勤怠一覧画面で「翌月」ボタンを押下した場合、翌月の勤怠情報が表示される
    public function test_admin_can_see_next_month_attendance_when_click_next_month_button()
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
            'name' => 'テスト 太郎',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-10',
            'clock_in_at' => '09:00',
            'clock_out_at' => '18:00',
            'status' => 'after',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-03'
        ]));

        $response->assertStatus(200);
        $response->assertSeeText('03/10');

        $response->assertSee(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-04'
        ]));

        $nextResponse = $this->get(route('admin.attendance.staff', [
            'user' => $user->id,
            'month' => '2026-04'
        ]));

        $nextResponse->assertStatus(200);
        $nextResponse->assertSeeText('04/10');
    }

    // 管理者がユーザーの勤怠一覧画面で「詳細」ボタンを押下した場合、該当日の勤怠詳細画面へ遷移する
    public function test_admin_can_navigate_to_attendance_detail_when_click_detail_link()
        {
            $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-03-10',
            'status'    => 'after',
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
        ]);

        $this->actingAs($admin);

        $list = $this->get(route('admin.attendance.staff', [
            'user'  => $user->id,
            'month' => '2026-03',
        ]));
        $list->assertStatus(200);

        $list->assertSee("/admin/attendance/{$user->id}/2026-03-10");

        $detail = $this->get("/admin/attendance/{$user->id}/2026-03-10");
        $detail->assertStatus(200);
    }

    // 管理者が修正申請一覧の「承認待ち」タブを開いた場合、全ユーザーの未承認申請が表示されること
    public function test_admin_pending_request_list_shows_all_users_pending_requests()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト花子',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => '2026-03-10',
            'status' => 'after',
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => '2026-03-11',
            'status' => 'after',
        ]);


        $this->actingAs($user1);
        $this->post(route('attendance.request', ['date' => '2026-03-10']), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'reason'       => 'テスト1',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
        ])->assertRedirect();

        $this->actingAs($user2);
        $this->post(route('attendance.request', ['date' => '2026-03-11']), [
            'clock_in_at'  => '10:00',
            'clock_out_at' => '19:00',
            'reason'       => 'テスト2',
            'breaks' => [
                1 => ['start' => '13:00', 'end' => '14:00'],
            ],
        ])->assertRedirect();

        $this->actingAs($admin);

        $list = $this->get('/stamp_correction_request/list?status=pending');
        $list->assertStatus(200);

        $list->assertSee('テスト太郎');
        $list->assertSee('テスト花子');
        $list->assertSee('テスト1');
        $list->assertSee('テスト2');
    }

    // 管理者が修正申請一覧画面の「承認済み」タブを開いた場合、全ユーザーの承認済み申請が表示されること
    public function test_admin_approved_request_list_shows_all_users_approved_requests()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'テスト花子',
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id'   => $user1->id,
            'work_date' => '2026-03-10',
            'status'    => 'after',
        ]);

        Attendance::create([
            'user_id'   => $user2->id,
            'work_date' => '2026-03-11',
            'status'    => 'after',
        ]);

        $this->actingAs($user1);
        $this->post(route('attendance.request', ['date' => '2026-03-10']), [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
            'reason'       => 'テスト1',
            'breaks' => [
                1 => ['start' => '12:00', 'end' => '13:00'],
            ],
        ])->assertRedirect();

        $req1 = AttendanceRequest::latest('id')->first();
        $this->assertNotNull($req1);
        $this->assertSame('pending', $req1->status);

        $this->actingAs($user2);
        $this->post(route('attendance.request', ['date' => '2026-03-11']), [
            'clock_in_at'  => '10:00',
            'clock_out_at' => '19:00',
            'reason'       => 'テスト2',
            'breaks' => [
                1 => ['start' => '13:00', 'end' => '14:00'],
            ],
        ])->assertRedirect();

        $req2 = AttendanceRequest::latest('id')->first();
        $this->assertNotNull($req2);
        $this->assertSame('pending', $req2->status);

        $this->actingAs($admin);

        $this->from('/stamp_correction_request/approve/'.$req1->id)
            ->post(route('stamp_correction_request.approve', [
                'attendance_correct_request_id' => $req1->id,
            ]), [
                'clock_in_at'  => '09:00',
                'clock_out_at' => '18:00',
                'breaks' => [
                    1 => ['start' => '12:00', 'end' => '13:00'],
                ],
                'reason' => 'テスト1',
            ])->assertRedirect();

        $this->from('/stamp_correction_request/approve/'.$req2->id)
            ->post(route('stamp_correction_request.approve', [
                'attendance_correct_request_id' => $req2->id,
            ]), [
                'clock_in_at'  => '10:00',
                'clock_out_at' => '19:00',
                'breaks' => [
                    1 => ['start' => '13:00', 'end' => '14:00'],
                ],
                'reason' => 'テスト2',
            ])->assertRedirect();

        $req1->refresh();
        $req2->refresh();
        $this->assertSame('approved', $req1->status);
        $this->assertSame('approved', $req2->status);

        $list = $this->get('/stamp_correction_request/list?tab=approved');
        $list->assertStatus(200);

        $list->assertSee('テスト1');
        $list->assertSee('テスト2');
    }

    // 管理者が修正申請の詳細画面を開いた場合、申請内容が正しく表示されること
    public function test_admin_can_see_correct_request_details_on_approve_show()
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

        $approveShow = $this->get(route('stamp_correction_request.approve.show', [
            'attendance_correct_request_id' => $req->id,
        ]));

        $approveShow->assertStatus(200);

        $approveShow->assertSee('テスト太郎');
        $approveShow->assertSee('2026年');
        $approveShow->assertSee('3月10日');
        $approveShow->assertSee('09:00');
        $approveShow->assertSee('18:00');
        $approveShow->assertSee('12:00');
        $approveShow->assertSee('13:00');
        $approveShow->assertSee('テスト');
    }
}