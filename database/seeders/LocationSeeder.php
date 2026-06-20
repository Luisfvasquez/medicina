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

        $states = [
            'Distrito Capital' => ['Caracas'],
            'Miranda' => ['Los Teques', 'Guarenas', 'Guatire', 'Chacao', 'Baruta', 'El Hatillo'],
            'Zulia' => ['Maracaibo', 'Cabimas', 'Ciudad Ojeda'],
            'Lara' => ['Barquisimeto', 'Cabudare', 'Carora'],
            'Carabobo' => ['Valencia', 'Puerto Cabello', 'Guacara'],
            'Aragua' => ['Maracay', 'Turmero', 'La Victoria'],
            'Anzoátegui' => ['Barcelona', 'Puerto La Cruz', 'El Tigre', 'Anaco'],
            'Bolívar' => ['Ciudad Guayana', 'Ciudad Bolívar'],
            'Táchira' => ['San Cristóbal', 'Táriba', 'Rubio'],
            'Mérida' => ['Mérida', 'El Vigía'],
            'Falcón' => ['Coro', 'Punto Fijo'],
            'Monagas' => ['Maturín'],
            'Sucre' => ['Cumaná', 'Carúpano'],
            'Portuguesa' => ['Acarigua', 'Guanare'],
            'Yaracuy' => ['San Felipe', 'Yaritagua'],
            'Barinas' => ['Barinas'],
            'Trujillo' => ['Valera', 'Trujillo'],
            'Nueva Esparta' => ['Porlamar', 'La Asunción'],
            'Guárico' => ['San Juan de los Morros', 'Valle de la Pascua', 'Calabozo'],
            'Apure' => ['San Fernando de Apure'],
            'Cojedes' => ['San Carlos'],
            'Delta Amacuro' => ['Tucupita'],
            'Amazonas' => ['Puerto Ayacucho'],
            'La Guaira' => ['La Guaira', 'Catia La Mar', 'Maiquetía'],
        ];

        foreach ($states as $stateName => $cities) {
            $state = $country->states()->create([
                'id' => Str::uuid(),
                'name' => $stateName
            ]);

            foreach ($cities as $cityName) {
                $state->cities()->create([
                    'id' => Str::uuid(),
                    'name' => $cityName
                ]);
            }
        }
    }
}
