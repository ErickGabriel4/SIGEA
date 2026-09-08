<?php

namespace App\Notifications;

use App\Models\AcaoCorretiva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifica o responsável designado por uma ação corretiva.
 */
class AcaoCorretivaAtribuida extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AcaoCorretiva $acao,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SIGEA — Ação corretiva atribuída a você')
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Você foi designado(a) responsável pela ação: \"{$this->acao->titulo}\".")
            ->line('Prazo: ' . $this->acao->prazo->format('d/m/Y'))
            ->action('Ver minhas ações', route('acoes.minhas'))
            ->line('Conclua a ação registrando a evidência de execução.');
    }
}
