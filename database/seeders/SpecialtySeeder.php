<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialty;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'Medicina General',
            'Pediatría',
            'Cardiología',
            'Ginecología y Obstetricia',
            'Dermatología',
            'Oftalmología',
            'Odontología',
            'Traumatología y Ortopedia'
        ];

        foreach ($specialties as $specialty) {
            Specialty::create([
                'id' => Str::uuid(),
                'name' => $specialty,
                'description' => 'Especialidad médica enfocada en ' . $specialty
            ]);
        }
    }
}
