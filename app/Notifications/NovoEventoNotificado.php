<?php

namespace App\Notifications;

use App\Models\EventoAdverso;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta os gestores de segurança sobre nova notificação aguardando triagem.
 */
class NovoEventoNotificado extends Notification
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
            ->subject("SIGEA — Nova notificação {$this->evento->protocolo} aguardando triagem")
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Uma nova notificação de evento adverso foi registrada: {$this->evento->protocolo}.")
            ->line('Setor: ' . ($this->evento->setorOcorrencia->nome ?? '—'))
            ->action('Ir para a fila de triagem', route('triagem.index'))
            ->line('Priorize a triagem conforme o SLA institucional.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'evento_id' => $this->evento->id,
            'protocolo' => $this->evento->protocolo,
            'titulo' => 'Nova notificação para triagem',
            'mensagem' => "Evento {$this->evento->protocolo} aguarda triagem.",
            'url' => route('triagem.index'),
        ];
    }
}
