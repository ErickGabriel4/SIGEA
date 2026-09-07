<?php

namespace App\Enums;

/**
 * Método estruturado de análise de causa raiz.
 */
enum MetodoInvestigacao: string
{
    case CincoPorques = 'cinco_porques';
    case Ishikawa = 'ishikawa';
    case ProtocoloLondres = 'protocolo_londres';
    case AnaliseSimplificada = 'analise_simplificada';

    public function label(): string
    {
        return match ($this) {
            self::CincoPorques => '5 Porquês',
            self::Ishikawa => 'Diagrama de Ishikawa',
            self::ProtocoloLondres => 'Protocolo de Londres',
            self::AnaliseSimplificada => 'Análise simplificada',
        };
    }
}
