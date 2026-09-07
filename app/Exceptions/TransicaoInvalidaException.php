<?php

namespace App\Exceptions;

use App\Enums\StatusEvento;
use Exception;

/**
 * Lançada quando uma transição de status fora da máquina de estados é solicitada.
 */
class TransicaoInvalidaException extends Exception
{
    public static function para(StatusEvento $de, StatusEvento $para, ?string $motivo = null): self
    {
        $mensagem = "Transição inválida: {$de->label()} → {$para->label()}.";

        if ($motivo !== null) {
            $mensagem .= " {$motivo}";
        }

        return new self($mensagem);
    }
}
