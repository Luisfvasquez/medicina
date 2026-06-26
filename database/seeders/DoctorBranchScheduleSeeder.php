<?php

namespace Database\Seeders;

use App\Models\ClinicBranch;
use App\Models\ClinicBranchMember;
use App\Models\DoctorSchedule;
use App\Models\User;
use App\Enums\Weekday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DoctorBranchScheduleSeeder extends Seeder
{
    /**
     * Links existing doctor schedules to specific branches based on
     * which branches they are members of. Creates additional schedules
     * for doctors at multiple branches.
     */
    public function run(): void
    {
        $doctors = User::where('role', 'DOCTOR')
            ->where('is_active', true)
            ->get();

        $this->command->info("Processing {$doctors->count()} doctors for branch-linked schedules");

        foreach ($doctors as $doctor) {
            // Get all branches where this doctor works
            $branches = ClinicBranchMember::where('user_id', $doctor->id)
                ->where('is_active', true)
                ->with('branch')
                ->get()
                ->pluck('branch')
                ->filter();

            if ($branches->isEmpty()) {
                $this->command->warn("Doctor {$doctor->email} has no branch memberships, skipping");
                continue;
            }

            // Get existing schedules for this doctor
            $existingSchedules = DoctorSchedule::where('user_id', $doctor->id)->get();

            // For each branch, ensure there's a schedule
            foreach ($branches as $branch) {
                $branchScheduleDays = ['MONDAY', 'WEDNESDAY', 'FRIDAY']; // Default days

                foreach ($branchScheduleDays as $day) {
                    // Check if schedule already exists for this branch
                    $existing = $existingSchedules->first(function ($s) use ($day, $branch) {
                        return $s->weekday->value === $day && $s->clinic_branch_id === $branch->id;
                    });

                    if ($existing) {
                        continue; // Already exists
                    }

                    // Check if there's a generic schedule (no branch) for this day
                    $genericSchedule = $existingSchedules->first(function ($s) use ($day) {
                        return $s->weekday->value === $day && $s->clinic_branch_id === null;
                    });

                    if ($genericSchedule) {
                        // Update generic schedule to be branch-specific
                        $genericSchedule->update([
                            'clinic_branch_id' => $branch->id,
                        ]);
                        $this->command->info("Linked {$doctor->email} {$day} to branch {$branch->name}");
                    } else {
                        // Create new branch-specific schedule
                        DoctorSchedule::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $doctor->id,
                            'clinic_branch_id' => $branch->id,
                            'weekday' => Weekday::from($day),
                            'start_time' => '08:00',
                            'end_time' => '17:00',
                            'appointment_duration' => 30,
                            'max_per_slot' => 1,
                            'is_active' => true,
                        ]);
                        $this->command->info("Created {$day} schedule for {$doctor->email} at {$branch->name}");
                    }
                }
            }

            // Remove branch_id from any schedules where the branch no longer exists
            $branchIds = $branches->pluck('id')->toArray();
            DoctorSchedule::where('user_id', $doctor->id)
                ->whereNotNull('clinic_branch_id')
                ->whereNotIn('clinic_branch_id', $branchIds)
                ->update(['clinic_branch_id' => null]);

            $this->command->info("Processed {$doctor->email}: {$branches->count()} branches");
        }
    }
}
