<?php

namespace App\Services;

use App\Models\LabAppointment;
use App\Models\LabRequest;
use App\Models\LabResult;
use Illuminate\Support\Facades\DB;

class LabAnalyticsService
{
    public function getDashboardMetrics(?int $providerProfileId = null): array
    {
        // 1. Most requested exams (for reactive purchasing forecast)
        $allRequests = LabRequest::query()->get();
        $examCounts = [];

        foreach ($allRequests as $req) {
            $exams = $req->exams_list ?? [];
            foreach ($exams as $exam) {
                $name = is_array($exam) ? ($exam['name'] ?? 'Examen') : (string) $exam;
                $examCounts[$name] = ($examCounts[$name] ?? 0) + 1;
            }
        }

        arsort($examCounts);
        $mostRequestedExams = [];
        foreach (array_slice($examCounts, 0, 5, true) as $name => $count) {
            $mostRequestedExams[] = [
                'exam_name' => $name,
                'requests_count' => $count,
            ];
        }

        // 2. Most performed exams (completed results)
        $totalCompletedResults = LabResult::where('status', 'completed')->count();

        // 3. Top frequent patients
        $topPatients = LabResult::select('patient_id', DB::raw('count(*) as total_exams'))
            ->whereNotNull('patient_id')
            ->groupBy('patient_id')
            ->orderByDesc('total_exams')
            ->limit(5)
            ->with('patient:id,first_name,last_name,document_number')
            ->get()
            ->map(fn ($r) => [
                'patient_id' => $r->patient_id,
                'patient_name' => $r->patient ? "{$r->patient->first_name} {$r->patient->last_name}" : "Paciente #{$r->patient_id}",
                'total_exams' => $r->total_exams,
            ]);

        // 4. Volume breakdown (internal vs external)
        $internalCount = LabRequest::where('is_external', false)->count();
        $externalCount = LabRequest::where('is_external', true)->count();

        return [
            'most_requested_exams' => $mostRequestedExams,
            'total_completed_results' => $totalCompletedResults,
            'top_patients' => $topPatients,
            'volume_breakdown' => [
                'internal' => $internalCount,
                'external' => $externalCount,
                'total' => $internalCount + $externalCount,
            ],
        ];
    }
}
