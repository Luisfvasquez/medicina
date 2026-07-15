<?php

namespace App\Traits;

use App\Models\MedicalBackground;
use App\Models\SurgicalHistory;
use App\Models\FamilyHistory;
use App\Models\Vaccination;
use Illuminate\Support\Str;

trait SyncsPatientDataBindings
{
    public function syncPatientDataBindings(): void
    {
        $schema = $this->form_schema_snapshot ?? $this->formTemplate?->schema_json;
        if (empty($schema)) {
            return;
        }

        if (is_string($schema)) {
            $schema = json_decode($schema, true);
        }

        $elements = $schema['canvas'] ?? [];
        if (empty($elements)) {
            return;
        }

        $patient = $this->patient;
        if (!$patient) {
            return;
        }

        $medicalBackground = MedicalBackground::firstOrCreate(['patient_id' => $patient->id], [
            'uuid' => (string) Str::uuid()
        ]);

        $dynamicData = $this->dynamic_data ?? [];

        $this->processBindings($elements, $dynamicData, $patient, $medicalBackground);
    }

    private function processBindings(array $elements, array $dynamicData, $patient, $medicalBackground): void
    {
        foreach ($elements as $el) {
            if ($el['type'] !== 'repeater' && !empty($el['children'])) {
                $this->processBindings($el['children'], $dynamicData, $patient, $medicalBackground);
                continue;
            }

            if (!empty($el['binding'])) {
                $binding = $el['binding'];
                $val = $dynamicData[$el['id']] ?? null;

                if ($el['type'] === 'repeater') {
                    $this->syncRepeaterBinding($el, $val, $patient);
                } else {
                    $this->syncSimpleBinding($binding, $val, $patient, $medicalBackground);
                }
            }
        }
    }

    private function syncSimpleBinding(string $binding, $val, $patient, $medicalBackground): void
    {
        $parts = explode('.', $binding);
        if (count($parts) !== 2) {
            return;
        }

        $entity = $parts[0];
        $field = $parts[1];

        if ($entity === 'patient') {
            $patient->update([$field => $val]);
        } elseif ($entity === 'medical_background') {
            $medicalBackground->update([$field => $val]);
        }
    }

    private function syncRepeaterBinding(array $el, $rows, $patient): void
    {
        if (!is_array($rows)) {
            return;
        }

        $binding = $el['binding'];
        $columns = $el['children'] ?? [];

        if ($binding === 'surgical_histories') {
            SurgicalHistory::where('patient_id', $patient->id)->delete();

            foreach ($rows as $row) {
                $mappedData = ['patient_id' => $patient->id, 'uuid' => (string) Str::uuid()];
                foreach ($columns as $col) {
                    if (!empty($col['binding'])) {
                        $mappedData[$col['binding']] = $row[$col['id']] ?? null;
                    }
                }
                
                if (count(array_filter($mappedData)) > 2) {
                    SurgicalHistory::create($mappedData);
                }
            }
        } elseif ($binding === 'family_histories') {
            FamilyHistory::where('patient_id', $patient->id)->delete();

            foreach ($rows as $row) {
                $mappedData = ['patient_id' => $patient->id, 'uuid' => (string) Str::uuid()];
                foreach ($columns as $col) {
                    if (!empty($col['binding'])) {
                        $mappedData[$col['binding']] = $row[$col['id']] ?? null;
                    }
                }

                if (count(array_filter($mappedData)) > 2) {
                    FamilyHistory::create($mappedData);
                }
            }
        } elseif ($binding === 'vaccinations') {
            Vaccination::where('patient_id', $patient->id)->delete();

            foreach ($rows as $row) {
                $mappedData = ['patient_id' => $patient->id, 'uuid' => (string) Str::uuid()];
                foreach ($columns as $col) {
                    if (!empty($col['binding'])) {
                        $mappedData[$col['binding']] = $row[$col['id']] ?? null;
                    }
                }

                if (count(array_filter($mappedData)) > 2) {
                    Vaccination::create($mappedData);
                }
            }
        }
    }
}
