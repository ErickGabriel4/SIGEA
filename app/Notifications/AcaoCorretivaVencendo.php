<?php

namespace App\Notifications;

use App\Models\AcaoCorretiva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta o responsável sobre ação corretiva com prazo próximo ou vencido.
 */
class AcaoCorretivaVencendo extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AcaoCorretiva $acao,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $vencida = $this->acao->prazo->isPast();

        return (new MailMessage)
            ->subject($vencida
                ? 'SIGEA — Ação corretiva VENCIDA'
                : 'SIGEA — Ação corretiva com prazo próximo')
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("A ação \"{$this->acao->titulo}\" " . ($vencida ? 'está vencida.' : 'vence em breve.'))
            ->line('Prazo: ' . $this->acao->prazo->format('d/m/Y'))
            ->action('Ver minhas ações', route('acoes.minhas'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'acao_id' => $this->acao->id,
            'titulo' => $this->acao->prazo->isPast() ? 'Ação vencida' : 'Ação com prazo próximo',
            'mensagem' => "\"{$this->acao->titulo}\" — prazo {$this->acao->prazo->format('d/m/Y')}.",
            'url' => route('acoes.minhas'),
        ];
    }
}
