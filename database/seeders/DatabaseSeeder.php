<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LocationSeeder::class,
            SpecialtySeeder::class,
            UserSeeder::class,
            ClinicSeeder::class,
            ClinicScheduleSeeder::class,
            FormTemplateSeeder::class,
            CatalogSeeder::class,
            DoctorScheduleSeeder::class,
            DoctorBranchScheduleSeeder::class,
        ]);
    }
}
