<?php

namespace App\Services;

use App\Enums\ClassificacaoIncidente;
use App\Enums\GrauDano;
use App\Enums\StatusEvento;
use App\Exceptions\TransicaoInvalidaException;
use App\Models\EventoAdverso;
use App\Models\EventoHistorico;
use App\Models\User;
use App\Notifications\EventoTransicionado;
use App\Notifications\NovoEventoNotificado;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados do evento adverso (Seção 6.1).
 * Toda transição válida grava linha em evento_historico e notifica os envolvidos.
 */
class WorkflowEventoService
{
    /** Transições permitidas: origem => destinos possíveis. */
    private const TRANSICOES = [
        'rascunho' => ['notificado', 'cancelado'],
        'notificado' => ['em_triagem'],
        'em_triagem' => ['em_investigacao', 'aguardando_acao', 'concluido', 'cancelado'],
        'em_investigacao' => ['aguardando_acao'],
        'aguardando_acao' => ['concluido', 'em_investigacao'],
        'concluido' => ['em_investigacao'],
    ];

    /** Transições que exigem justificativa obrigatória. */
    private const EXIGEM_JUSTIFICATIVA = [
        'em_triagem:cancelado',
        'aguardando_acao:em_investigacao',
        'concluido:em_investigacao',
    ];

    /**
     * Executa a transição de status, validando a máquina de estados e as
     * pré-condições de negócio. Lança TransicaoInvalidaException em violação.
     */
    public function transicionar(
        EventoAdverso $evento,
        StatusEvento $novoStatus,
        ?User $usuario = null,
        ?string $observacao = null,
        string $acao = 'transicao'
    ): EventoAdverso {
        $statusAtual = $evento->status;

        if (! $this->transicaoPermitida($statusAtual, $novoStatus)) {
            throw TransicaoInvalidaException::para($statusAtual, $novoStatus);
        }

        $this->validarPreCondicoes($evento, $statusAtual, $novoStatus, $observacao);

        return DB::transaction(function () use ($evento, $statusAtual, $novoStatus, $usuario, $observacao, $acao) {
            $evento->status = $novoStatus;
            $this->registrarMarcosTemporais($evento, $novoStatus);
            $evento->save();

            EventoHistorico::create([
                'evento_adverso_id' => $evento->id,
                'user_id' => $usuario?->id,
                'status_anterior' => $statusAtual->value,
                'status_novo' => $novoStatus->value,
                'acao' => $acao,
                'observacao' => $observacao,
            ]);

            $this->notificarEnvolvidos($evento, $novoStatus, $acao);

            return $evento;
        });
    }

    /** Verifica se o par origem→destino consta da máquina de estados. */
    public function transicaoPermitida(StatusEvento $de, StatusEvento $para): bool
    {
        return in_array($para->value, self::TRANSICOES[$de->value] ?? [], true);
    }

    /** Pré-condições de negócio de cada transição (tabela da Seção 6.1). */
    private function validarPreCondicoes(
        EventoAdverso $evento,
        StatusEvento $de,
        StatusEvento $para,
        ?string $observacao
    ): void {
        $par = "{$de->value}:{$para->value}";

        if (in_array($par, self::EXIGEM_JUSTIFICATIVA, true) && blank($observacao)) {
            throw TransicaoInvalidaException::para($de, $para, 'Justificativa obrigatória.');
        }

        match ($par) {
            'notificado:em_triagem' => $this->exigir(
                $evento->gestor_responsavel_id !== null,
                $de, $para, 'Um gestor deve se atribuir como responsável antes de triar.'
            ),
            'em_triagem:em_investigacao',
            'em_triagem:aguardando_acao' => $this->exigir(
                $evento->classificacaoCompleta(),
                $de, $para, 'Classificação incompleta (categoria, classificação, grau de dano, evitabilidade, probabilidade e severidade são obrigatórios).'
            ),
            'em_triagem:concluido' => $this->exigir(
                $evento->grau_dano === GrauDano::Nenhum
                    && in_array($evento->classificacao, [
                        ClassificacaoIncidente::NearMiss,
                        ClassificacaoIncidente::CircunstanciaNotificavel,
                    ], true),
                $de, $para, 'Conclusão direta permitida apenas para near miss ou circunstância notificável sem dano.'
            ),
            'em_investigacao:aguardando_acao' => $this->exigir(
                $evento->investigacao?->status === \App\Enums\StatusInvestigacao::Concluida
                    && $evento->acoesCorretivas()->exists(),
                $de, $para, 'A investigação deve estar concluída e deve existir ao menos uma ação corretiva.'
            ),
            'aguardando_acao:concluido' => $this->exigir(
                ! $evento->acoesCorretivas()
                    ->whereNotIn('status', ['concluida', 'cancelada'])
                    ->exists(),
                $de, $para, 'Todas as ações corretivas devem estar concluídas ou canceladas.'
            ),
            default => null,
        };
    }

    /** Lança exceção descritiva quando a pré-condição não é satisfeita. */
    private function exigir(bool $condicao, StatusEvento $de, StatusEvento $para, string $motivo): void
    {
        if (! $condicao) {
            throw TransicaoInvalidaException::para($de, $para, $motivo);
        }
    }

    /** Marca triado_em / investigado_em / concluido_em conforme o destino. */
    private function registrarMarcosTemporais(EventoAdverso $evento, StatusEvento $novo): void
    {
        match ($novo) {
            StatusEvento::EmTriagem => $evento->triado_em = $evento->triado_em ?? now(),
            StatusEvento::EmInvestigacao => $evento->investigado_em = $evento->investigado_em ?? now(),
            StatusEvento::Concluido => $evento->concluido_em = now(),
            default => null,
        };

        // Reabertura limpa o marco de conclusão
        if ($novo === StatusEvento::EmInvestigacao && $evento->concluido_em !== null) {
            $evento->concluido_em = null;
        }
    }

    /**
     * Notifica o notificante (exceto anônimas) e o gestor responsável;
     * ao entrar em "notificado", alerta todos os gestores de segurança.
     */
    private function notificarEnvolvidos(EventoAdverso $evento, StatusEvento $novo, string $acao): void
    {
        if ($novo === StatusEvento::Notificado) {
            $gestores = User::ativos()->permission('evento.triar')->get();
            foreach ($gestores as $gestor) {
                $gestor->notify(new NovoEventoNotificado($evento));
            }
        }

        if (! $evento->anonima && $evento->notificante !== null) {
            $evento->notificante->notify(new EventoTransicionado($evento, $acao));
        }

        if ($evento->gestorResponsavel !== null) {
            $evento->gestorResponsavel->notify(new EventoTransicionado($evento, $acao));
        }
    }
}
