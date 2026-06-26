<?php

namespace Database\Seeders;

use App\Models\ClinicBranch;
use App\Models\ClinicSchedule;
use App\Enums\Weekday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClinicScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultHours = [
            'MONDAY'    => ['start' => '07:00', 'end' => '20:00'],
            'TUESDAY'   => ['start' => '07:00', 'end' => '20:00'],
            'WEDNESDAY' => ['start' => '07:00', 'end' => '20:00'],
            'THURSDAY'  => ['start' => '07:00', 'end' => '20:00'],
            'FRIDAY'    => ['start' => '07:00', 'end' => '20:00'],
            'SATURDAY'  => ['start' => '08:00', 'end' => '14:00'],
        ];

        $branches = ClinicBranch::all();

        $this->command->info("Processing {$branches->count()} branches");

        foreach ($branches as $branch) {
            foreach ($defaultHours as $day => $hours) {
                $exists = ClinicSchedule::where('clinic_branch_id', $branch->id)
                    ->where('weekday', Weekday::from($day))
                    ->exists();

                if (!$exists) {
                    ClinicSchedule::create([
                        'uuid' => Str::uuid(),
                        'clinic_branch_id' => $branch->id,
                        'weekday' => Weekday::from($day),
                        'start_time' => $hours['start'],
                        'end_time' => $hours['end'],
                        'is_active' => true,
                    ]);

                    $this->command->info("Created {$day} schedule for branch {$branch->name}");
                } else {
                    $this->command->info("Branch {$branch->name} already has {$day}");
                }
            }
        }
    }
}
