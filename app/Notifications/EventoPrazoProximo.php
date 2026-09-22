<?php

namespace App\Notifications;

use App\Models\EventoAdverso;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta o gestor responsável sobre evento com prazo de conclusão próximo.
 */
class EventoPrazoProximo extends Notification
{
    use Queueable;

    public function __construct(
        public readonly EventoAdverso $evento,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SIGEA — Prazo do evento {$this->evento->protocolo} se aproxima")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("O evento {$this->evento->protocolo} tem prazo de conclusão em "
                . $this->evento->prazo_conclusao->format('d/m/Y') . '.')
            ->action('Ver evento', route('eventos.show', $this->evento));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'evento_id' => $this->evento->id,
            'titulo' => 'Prazo de conclusão próximo',
            'mensagem' => "Evento {$this->evento->protocolo} — prazo "
                . $this->evento->prazo_conclusao->format('d/m/Y') . '.',
            'url' => route('eventos.show', $this->evento),
        ];
    }
}
