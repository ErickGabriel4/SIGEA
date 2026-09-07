<?php

namespace App\Policies;

use App\Enums\StatusEvento;
use App\Models\EventoAdverso;
use App\Models\User;

/**
 * Regras de propriedade e acesso ao evento adverso.
 * Negação por padrão: só é permitido o que está explicitamente autorizado.
 */
class EventoAdversoPolicy
{
    /** Gestores/admins veem tudo; notificadores veem a própria lista (escopo na query). */
    public function viewAny(User $usuario): bool
    {
        return $usuario->can('evento.ver_todos') || $usuario->can('evento.ver_proprios');
    }

    /** Ver um evento: gestor vê todos; notificador só os próprios não anônimos. */
    public function view(User $usuario, EventoAdverso $evento): bool
    {
        if ($usuario->can('evento.ver_todos')) {
            return true;
        }

        return $usuario->can('evento.ver_proprios')
            && ! $evento->anonima
            && $evento->notificante_id === $usuario->id;
    }

    public function create(User $usuario): bool
    {
        return $usuario->can('evento.criar');
    }

    /** Editar: apenas o dono, apenas em rascunho. */
    public function update(User $usuario, EventoAdverso $evento): bool
    {
        return $evento->status === StatusEvento::Rascunho
            && $evento->notificante_id === $usuario->id;
    }

    /** Excluir: apenas o dono, apenas em rascunho. */
    public function delete(User $usuario, EventoAdverso $evento): bool
    {
        return $evento->status === StatusEvento::Rascunho
            && $evento->notificante_id === $usuario->id;
    }

    /** Enviar rascunho (rascunho → notificado): apenas o dono. */
    public function enviar(User $usuario, EventoAdverso $evento): bool
    {
        return $evento->status === StatusEvento::Rascunho
            && $evento->notificante_id === $usuario->id;
    }

    /** Cancelar: dono em rascunho, ou gestor em triagem. */
    public function cancelar(User $usuario, EventoAdverso $evento): bool
    {
        if ($evento->status === StatusEvento::Rascunho) {
            return $evento->notificante_id === $usuario->id;
        }

        return $evento->status === StatusEvento::EmTriagem && $usuario->can('evento.triar');
    }

    /** Anexar arquivos: quem pode ver e o evento ainda não foi concluído/cancelado. */
    public function anexar(User $usuario, EventoAdverso $evento): bool
    {
        return $this->view($usuario, $evento)
            && ! in_array($evento->status, [StatusEvento::Concluido, StatusEvento::Cancelado], true);
    }

    /** Comentar: quem pode ver o evento. */
    public function comentar(User $usuario, EventoAdverso $evento): bool
    {
        return $this->view($usuario, $evento);
    }
}
