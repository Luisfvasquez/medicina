<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DoctorSchedule;
use App\Enums\Weekday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DoctorScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSchedules = [
            'MONDAY' => ['start' => '08:00', 'end' => '17:00'],
            'TUESDAY' => ['start' => '08:00', 'end' => '17:00'],
            'WEDNESDAY' => ['start' => '08:00', 'end' => '17:00'],
            'THURSDAY' => ['start' => '08:00', 'end' => '17:00'],
            'FRIDAY' => ['start' => '08:00', 'end' => '17:00'],
            'SATURDAY' => ['start' => '09:00', 'end' => '13:00'],
        ];

        $doctors = User::where('role', 'DOCTOR')
            ->where('is_active', true)
            ->get();

        $this->command->info("Processing {$doctors->count()} doctors");

        foreach ($doctors as $doctor) {
            // Default schedule: Mon, Wed, Fri 8am-5pm
            $defaultDays = ['MONDAY', 'WEDNESDAY', 'FRIDAY'];

            $existingSchedules = DoctorSchedule::where('user_id', $doctor->id)
                ->pluck('weekday')
                ->map(fn($w) => $w->value)
                ->toArray();

            foreach ($defaultDays as $day) {
                if (!in_array($day, $existingSchedules)) {
                    DoctorSchedule::create([
                        'uuid' => Str::uuid(),
                        'user_id' => $doctor->id,
                        'weekday' => Weekday::from($day),
                        'start_time' => $defaultSchedules[$day]['start'],
                        'end_time' => $defaultSchedules[$day]['end'],
                        'appointment_duration' => 30,
                        'max_per_slot' => 1,
                        'is_active' => true,
                    ]);

                    $this->command->info("Created {$day} schedule for {$doctor->email}");
                } else {
                    $this->command->info("{$doctor->email} already has {$day}");
                }
            }
        }
    }
}
