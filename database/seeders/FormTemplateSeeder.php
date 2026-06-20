<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FormTemplate;
use Illuminate\Support\Str;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Medicina General Global Template
        FormTemplate::create([
            'uuid' => Str::uuid(),
            'user_id' => null, // Global
            'specialty' => 'Medicina General',
            'schema_json' => json_encode([
                [
                    'name' => 'temperature',
                    'label' => 'Temperatura (°C)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '36.5'
                ],
                [
                    'name' => 'blood_pressure',
                    'label' => 'Presión Arterial (mmHg)',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '120/80'
                ],
                [
                    'name' => 'heart_rate',
                    'label' => 'Frecuencia Cardíaca (LPM)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '72'
                ],
                [
                    'name' => 'respiratory_rate',
                    'label' => 'Frecuencia Respiratoria (RPM)',
                    'type' => 'number',
                    'required' => false,
                    'placeholder' => '16'
                ],
                [
                    'name' => 'weight',
                    'label' => 'Peso Corporal (kg)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '70.0'
                ],
                [
                    'name' => 'height',
                    'label' => 'Estatura (cm)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '170'
                ],
                [
                    'name' => 'physical_findings',
                    'label' => 'Hallazgos del Examen Físico',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Normocárdico, hidratado...'
                ]
            ])
        ]);

        // 2. Cardiología Global Template
        FormTemplate::create([
            'uuid' => Str::uuid(),
            'user_id' => null, // Global
            'specialty' => 'Cardiología',
            'schema_json' => json_encode([
                [
                    'name' => 'electrocardiogram_summary',
                    'label' => 'Resumen de Electrocardiograma (ECG)',
                    'type' => 'textarea',
                    'required' => true,
                    'placeholder' => 'Ritmo sinusal, frecuencia...'
                ],
                [
                    'name' => 'blood_pressure_sys',
                    'label' => 'Presión Arterial Sistólica (mmHg)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '120'
                ],
                [
                    'name' => 'blood_pressure_dia',
                    'label' => 'Presión Arterial Diastólica (mmHg)',
                    'type' => 'number',
                    'required' => true,
                    'placeholder' => '80'
                ],
                [
                    'name' => 'cardiac_murmurs',
                    'label' => 'Presencia de Soplos Cardíacos',
                    'type' => 'select',
                    'required' => true,
                    'options' => ['No', 'Grado I', 'Grado II', 'Grado III', 'Grado IV']
                ],
                [
                    'name' => 'chest_pain_episodes',
                    'label' => 'Episodios de dolor de pecho (últimos 30 días)',
                    'type' => 'number',
                    'required' => false,
                    'placeholder' => '0'
                ],
                [
                    'name' => 'cardiac_notes',
                    'label' => 'Notas adicionales del cardiólogo',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => 'Paciente refiere palpitaciones ocasionales...'
                ]
            ])
        ]);
    }
}
