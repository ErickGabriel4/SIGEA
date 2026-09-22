<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Resumo semanal de indicadores enviado aos gestores (segunda-feira, 08h).
 */
class ResumoSemanalIndicadores extends Notification
{
    use Queueable;

    /**
     * @param  array<string, float|int|null>  $indicadores
     */
    public function __construct(
        public readonly array $indicadores,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SIGEA — Resumo semanal de indicadores de segurança do paciente')
            ->greeting("Olá, {$notifiable->nome}!")
            ->line('Resumo dos últimos 7 dias:')
            ->line('• Eventos notificados: ' . ($this->indicadores['total_eventos'] ?? 0))
            ->line('• Taxa de eventos com dano: ' . ($this->indicadores['taxa_com_dano'] ?? '—') . '%')
            ->line('• Taxa de evitabilidade: ' . ($this->indicadores['taxa_evitabilidade'] ?? '—') . '%')
            ->line('• Ações em atraso: ' . ($this->indicadores['acoes_em_atraso'] ?? 0))
            ->action('Abrir painel', route('painel.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => 'Resumo semanal de indicadores',
            'mensagem' => 'Eventos na semana: ' . ($this->indicadores['total_eventos'] ?? 0)
                . ' · Ações em atraso: ' . ($this->indicadores['acoes_em_atraso'] ?? 0),
            'url' => route('painel.index'),
        ];
    }
}
