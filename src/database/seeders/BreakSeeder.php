<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;

class BreakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::with('breaks')->get();

        foreach ($attendances as $att) {
            // 既に休憩があるならスキップ
            if ($att->breaks->isNotEmpty()) continue;

            BreakTime::create([
                'attendance_id' => $att->id,
                'break_no'      => 1,
                'break_start_at'=> '12:00:00',
                'break_end_at'  => '13:00:00',
            ]);

        }

    }
}
