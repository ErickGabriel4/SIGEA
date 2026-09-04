<?php

namespace App\Enums;

/**
 * Classificação do incidente segundo a ICPS (WHO, 2009).
 */
enum ClassificacaoIncidente: string
{
    case CircunstanciaNotificavel = 'circunstancia_notificavel';
    case NearMiss = 'near_miss';
    case IncidenteSemDano = 'incidente_sem_dano';
    case EventoAdverso = 'evento_adverso';
    case NeverEvent = 'never_event';

    public function label(): string
    {
        return match ($this) {
            self::CircunstanciaNotificavel => 'Circunstância notificável',
            self::NearMiss => 'Near miss (quase-erro)',
            self::IncidenteSemDano => 'Incidente sem dano',
            self::EventoAdverso => 'Evento adverso',
            self::NeverEvent => 'Never event',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::CircunstanciaNotificavel => 'secondary',
            self::NearMiss => 'info',
            self::IncidenteSemDano => 'primary',
            self::EventoAdverso => 'warning',
            self::NeverEvent => 'danger',
        };
    }
}
