<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$doctorUuid = $argv[1] ?? '542e0e28-86a3-434d-8dde-7cbffb98fc3b';
$branchUuid = $argv[2] ?? '86896587-02fd-4c68-987a-0d059ebb8e27';

$doctor = App\Models\User::where('uuid', $doctorUuid)->first();
$branch = App\Models\ClinicBranch::where('uuid', $branchUuid)->first();

echo "Doctor: " . ($doctor ? $doctor->full_name . " (id={$doctor->id})" : "NOT FOUND") . PHP_EOL;
echo "Branch: " . ($branch ? $branch->name . " (id={$branch->id})" : "NOT FOUND") . PHP_EOL;
echo PHP_EOL;

if ($doctor) {
    $ds = App\Models\DoctorSchedule::where('user_id', $doctor->id)->get();
    echo "ALL DoctorSchedules:" . PHP_EOL;
    foreach ($ds as $s) {
        echo "  " . $s->weekday->value . " | branch_id={$s->clinic_branch_id} | {$s->start_time} - {$s->end_time}" . PHP_EOL;
    }
    echo PHP_EOL;
}

if ($branch) {
    $cs = App\Models\ClinicSchedule::where('clinic_branch_id', $branch->id)->where('weekday', 'FRIDAY')->first();
    echo "ClinicSchedule (FRIDAY):" . PHP_EOL;
    if ($cs) {
        echo "  {$cs->start_time} - {$cs->end_time}" . PHP_EOL;
    } else {
        echo "  NOT FOUND" . PHP_EOL;
    }
}
