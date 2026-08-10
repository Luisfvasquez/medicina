<?php

namespace App\Jobs;

use App\Models\Prescription;
use App\Services\PrescriptionMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchPrescriptionWithInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $prescription;

    /**
     * Create a new job instance.
     *
     * @param Prescription $prescription
     */
    public function __construct(Prescription $prescription)
    {
        $this->prescription = $prescription;
    }

    /**
     * Execute the job.
     */
    public function handle(PrescriptionMatchingService $matchingService): void
    {
        $matchingService->processMatch($this->prescription);
    }
}
