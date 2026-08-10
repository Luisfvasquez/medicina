<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Prescription;

class AutoMatchingEmptyInventoryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $prescription;

    /**
     * Create a new notification instance.
     */
    public function __construct(Prescription $prescription)
    {
        $this->prescription = $prescription;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Can add 'mail' later
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inventory_empty_matching',
            'title' => 'Oportunidad de Venta Perdida',
            'message' => 'Un paciente cercano está buscando medicamentos que no tienes en stock, pero tienes el Auto-Matching activado. ¡Actualiza tu inventario!',
            'prescription_id' => $this->prescription->id,
            'url' => '/dashboard/pharmacy/inventory',
        ];
    }
}
