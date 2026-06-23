<?php

namespace App\Http\Controllers\Api\V1\Phase5;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfExportController extends Controller
{
    public function consultation(string $id): Response
    {
        $consultation = Consultation::with(['patient', 'user', 'clinicBranch', 'vitalSign'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.consultation', ['consultation' => $consultation]);

        return $pdf->download("consultation-{$consultation->uuid}.pdf");
    }

    public function prescription(string $id): Response
    {
        $prescription = Prescription::with(['patient', 'user', 'clinicBranch', 'items.medication'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.prescription', ['prescription' => $prescription]);

        return $pdf->download("prescription-{$prescription->uuid}.pdf");
    }

    public function invoice(string $id): Response
    {
        $invoice = Invoice::with(['patient', 'user', 'clinicBranch', 'items', 'payments'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.invoice', ['invoice' => $invoice]);

        return $pdf->download("invoice-{$invoice->uuid}.pdf");
    }

    public function medicalDocument(string $id): Response
    {
        $document = MedicalDocument::with(['patient', 'user', 'clinicBranch'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdfs.medical_document', ['document' => $document]);

        return $pdf->download("document-{$document->uuid}.pdf");
    }
}
