<?php

namespace App\Notifications;

use App\Models\EventoAdverso;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica notificante e gestor responsável a cada transição de status do evento.
 */
class EventoTransicionado extends Notification
{
    use Queueable;

    public function __construct(
        public readonly EventoAdverso $evento,
        public readonly string $acao,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SIGEA — Evento {$this->evento->protocolo}: {$this->evento->status->label()}")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("O evento {$this->evento->protocolo} mudou de situação.")
            ->line("Nova situação: {$this->evento->status->label()}.")
            ->action('Ver evento', route('eventos.show', $this->evento))
            ->line('Obrigado por contribuir com a segurança do paciente.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'evento_id' => $this->evento->id,
            'protocolo' => $this->evento->protocolo,
            'titulo' => "Evento {$this->evento->protocolo}",
            'mensagem' => "Situação atualizada para: {$this->evento->status->label()}.",
            'url' => route('eventos.show', $this->evento),
        ];
    }
}
