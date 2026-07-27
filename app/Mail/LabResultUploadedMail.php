<?php

namespace App\Mail;

use App\Models\LabResult;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LabResultUploadedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LabResult $labResult, public ?string $pdfPath = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'LUCA Health OS - Tus Resultados de Laboratorio están Listos',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlString(),
        );
    }

    public function attachments(): array
    {
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            return [
                Attachment::fromPath($this->pdfPath)
                    ->as('Reporte_Resultados_Laboratorio.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }

    private function buildHtmlString(): string
    {
        $patientName = $this->labResult->patient?->first_name ?? 'Paciente';
        $performedAt = $this->labResult->performed_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $notes = $this->labResult->notes ?? 'Sin observaciones adicionales.';

        return "
        <div style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px;'>
          <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;'>
            <div style='display: inline-block; padding: 6px 12px; background: #e0f2fe; color: #0284c7; font-weight: bold; border-radius: 9999px; font-size: 12px; margin-bottom: 16px;'>
              ● LUCA Health OS — Resultados Disponibles
            </div>
            <h2 style='color: #0f172a; margin-top: 0;'>¡Hola, {$patientName}!</h2>
            <p style='color: #475569; font-size: 14px; line-height: 1.6;'>
              Tus resultados de estudio de laboratorio procesados el <strong>{$performedAt}</strong> ya están disponibles para consulta y descarga en tu portal.
            </p>
            <div style='background: #f1f5f9; border-left: 4px solid #23DCE1; padding: 16px; border-radius: 8px; margin: 20px 0;'>
              <strong style='color: #0f172a; font-size: 13px;'>Notas del Profesional:</strong>
              <p style='color: #334155; font-size: 13px; margin: 4px 0 0 0;'>{$notes}</p>
            </div>
            <p style='color: #475569; font-size: 13px;'>
              Encontrarás el documento PDF completo adjunto a este correo electrónico. También podés ingresar a tu aplicación LUCA en cualquier momento para consultar las imágenes y anexos.
            </p>
            <div style='margin-top: 24px; text-align: center;'>
              <a href='https://luca.health/dashboard/results' style='background: #23DCE1; color: #0f172a; padding: 12px 24px; font-weight: bold; text-decoration: none; border-radius: 12px; display: inline-block;'>
                Ver en LUCA Health OS
              </a>
            </div>
          </div>
        </div>
        ";
    }
}
