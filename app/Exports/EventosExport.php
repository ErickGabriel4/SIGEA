<?php

namespace App\Exports;

use App\Models\EventoAdverso;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exportação da base de eventos em CSV/XLSX (US29).
 * Sem dados identificáveis: paciente aparece somente pelo código pseudonimizado.
 */
class EventosExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private readonly array $filtros = [],
    ) {
    }

    public function query(): Builder
    {
        return EventoAdverso::query()
            ->whereNotIn('status', ['rascunho'])
            ->with(['categoria.pai', 'setorOcorrencia', 'gestorResponsavel'])
            ->when($this->filtros['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filtros['setor_id'] ?? null, fn ($q, $v) => $q->where('setor_ocorrencia_id', $v))
            ->when($this->filtros['categoria_id'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('categoria_evento_id', $v)
                        ->orWhereHas('categoria', fn ($c) => $c->where('categoria_pai_id', $v));
                });
            })
            ->when($this->filtros['grau_dano'] ?? null, fn ($q, $v) => $q->where('grau_dano', $v))
            ->when($this->filtros['inicio'] ?? null, fn ($q, $v) => $q->whereDate('data_ocorrencia', '>=', $v))
            ->when($this->filtros['fim'] ?? null, fn ($q, $v) => $q->whereDate('data_ocorrencia', '<=', $v))
            ->orderByDesc('data_ocorrencia');
    }

    public function headings(): array
    {
        return [
            'Protocolo', 'Data da ocorrência', 'Turno', 'Setor', 'Categoria',
            'Subcategoria', 'Classificação', 'Grau de dano', 'Evitabilidade',
            'Probabilidade', 'Severidade', 'Score', 'Nível de risco', 'Prioridade',
            'Status', 'Anônima', 'Paciente (código)', 'Gestor responsável',
            'Prazo de conclusão', 'Concluído em', 'Notificável ANVISA', 'Protocolo NOTIVISA',
        ];
    }

    public function map($evento): array
    {
        return [
            $evento->protocolo,
            $evento->data_ocorrencia->format('d/m/Y'),
            $evento->turno->label(),
            $evento->setorOcorrencia->nome ?? '—',
            $evento->categoria->pai->nome ?? $evento->categoria->nome ?? '—',
            $evento->categoria->nome ?? '—',
            $evento->classificacao->label(),
            $evento->grau_dano->label(),
            $evento->evitabilidade->label(),
            $evento->probabilidade,
            $evento->severidade,
            $evento->score_risco,
            $evento->nivel_risco?->label() ?? '—',
            $evento->prioridade->label(),
            $evento->status->label(),
            $evento->anonima ? 'Sim' : 'Não',
            $evento->paciente_codigo ?? '—',
            $evento->gestorResponsavel->nome ?? '—',
            $evento->prazo_conclusao?->format('d/m/Y') ?? '—',
            $evento->concluido_em?->format('d/m/Y') ?? '—',
            $evento->notificavel_anvisa ? 'Sim' : 'Não',
            $evento->protocolo_notivisa ?? '—',
        ];
    }
}
