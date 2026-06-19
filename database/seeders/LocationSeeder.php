<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use Illuminate\Support\Str;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::create([
            'id' => Str::uuid(),
            'name' => 'Venezuela',
            'code' => 'VE'
        ]);

        $state1 = $country->states()->create([
            'id' => Str::uuid(),
            'name' => 'Distrito Capital'
        ]);

        $state1->cities()->create([
            'id' => Str::uuid(),
            'name' => 'Caracas'
        ]);

        $state2 = $country->states()->create([
            'id' => Str::uuid(),
            'name' => 'Miranda'
        ]);

        $state2->cities()->createMany([
            ['id' => Str::uuid(), 'name' => 'Los Teques'],
            ['id' => Str::uuid(), 'name' => 'Guarenas'],
            ['id' => Str::uuid(), 'name' => 'Guatire']
        ]);
    }
}
