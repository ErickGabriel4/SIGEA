<?php

namespace App\Enums;

/**
 * Estados do ciclo de vida do evento adverso (máquina de estados — Seção 6.1).
 */
enum StatusEvento: string
{
    case Rascunho = 'rascunho';
    case Notificado = 'notificado';
    case EmTriagem = 'em_triagem';
    case EmInvestigacao = 'em_investigacao';
    case AguardandoAcao = 'aguardando_acao';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Notificado => 'Notificado',
            self::EmTriagem => 'Em triagem',
            self::EmInvestigacao => 'Em investigação',
            self::AguardandoAcao => 'Aguardando ação',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    /** Classe de cor (badge) do AdminLTE/Bootstrap. */
    public function cor(): string
    {
        return match ($this) {
            self::Rascunho => 'secondary',
            self::Notificado => 'info',
            self::EmTriagem => 'primary',
            self::EmInvestigacao => 'warning',
            self::AguardandoAcao => 'orange',
            self::Concluido => 'success',
            self::Cancelado => 'dark',
        };
    }
}
