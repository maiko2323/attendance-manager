<?php

namespace Database\Seeders;


use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $users = User::where('role', 'user')->get();

        $months = [
            [
                'year' => 2026,
                'month' => 1,
                'end_day' => 31,
            ],
            [
                'year' => 2026,
                'month' => 2,
                'end_day' => Carbon::today()->day,
            ],
        ];

        foreach ($users as $user) {
            foreach ($months as $m) {
                for ($day = 1; $day <= $m['end_day']; $day++) {

                    $date = Carbon::create(
                        $m['year'],
                        $m['month'],
                        $day
                    );

                    if ($date->month === 1 && $date->day <= 4) {
                        continue;
                    }

                    if ($date->isSunday()) {
                        continue;
                    }

                    if (random_int(1, 100) <= 10) {
                        continue;
                    }

                    Attendance::updateOrCreate(
                        [
                            'user_id'   => $user->id,
                            'work_date' => $date->toDateString(),
                        ],
                        [
                            'clock_in_at'  => $date->copy()->setTime(9, 0),
                            'clock_out_at' => $date->copy()->setTime(18, 0),
                            'status'       => 'after',
                        ]
                    );
                }
            }
        }
    }
}
