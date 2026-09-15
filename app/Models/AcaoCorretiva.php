<?php

namespace App\Models;

use App\Enums\Eficacia;
use App\Enums\StatusAcao;
use App\Enums\TipoAcao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ação corretiva/preventiva decorrente da tratativa do evento.
 */
class AcaoCorretiva extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'acoes_corretivas';

    protected $fillable = [
        'evento_adverso_id',
        'investigacao_id',
        'titulo',
        'descricao',
        'tipo',
        'responsavel_id',
        'setor_id',
        'prazo',
        'data_conclusao',
        'status',
        'eficacia',
        'evidencia_conclusao',
    ];

    protected function casts(): array
    {
        return [
            'prazo' => 'date',
            'data_conclusao' => 'date',
            'tipo' => TipoAcao::class,
            'status' => StatusAcao::class,
            'eficacia' => Eficacia::class,
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoAdverso::class, 'evento_adverso_id');
    }

    public function investigacao(): BelongsTo
    {
        return $this->belongsTo(Investigacao::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    // ── Escopos ────────────────────────────────────────────────────────

    /** Ações vencidas ainda abertas (alvo do job diário de atraso). */
    public function scopeVencidasAbertas(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [StatusAcao::Pendente, StatusAcao::EmAndamento])
            ->whereDate('prazo', '<', today());
    }

    public function scopeDoResponsavel(Builder $query, User $usuario): Builder
    {
        return $query->where('responsavel_id', $usuario->id);
    }

    /** Concluída dentro do prazo. */
    public function getConcluidaNoPrazoAttribute(): ?bool
    {
        if ($this->status !== StatusAcao::Concluida || $this->data_conclusao === null) {
            return null;
        }

        return $this->data_conclusao->lte($this->prazo);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['titulo', 'status', 'responsavel_id', 'prazo', 'data_conclusao', 'eficacia'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('acao_corretiva');
    }
}
