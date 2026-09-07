<?php

namespace App\Models;

use App\Enums\MetodoInvestigacao;
use App\Enums\StatusInvestigacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Investigação de causa raiz — relação 1:1 com o evento adverso.
 */
class Investigacao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investigacoes';

    protected $fillable = [
        'evento_adverso_id',
        'responsavel_id',
        'metodo',
        'data_inicio',
        'data_prevista',
        'data_conclusao',
        'descricao_analise',
        'causa_raiz',
        'conclusao',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_prevista' => 'date',
            'data_conclusao' => 'date',
            'metodo' => MetodoInvestigacao::class,
            'status' => StatusInvestigacao::class,
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoAdverso::class, 'evento_adverso_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function causas(): HasMany
    {
        return $this->hasMany(InvestigacaoCausa::class)->orderBy('categoria')->orderBy('ordem');
    }

    // ── Regras auxiliares ──────────────────────────────────────────────

    /**
     * Pode ser concluída: exige ao menos uma causa marcada como raiz.
     * A exigência de ação corretiva cadastrada entra quando essa etapa existir.
     */
    public function podeConcluir(): bool
    {
        return $this->causas()->where('eh_causa_raiz', true)->exists();
    }

    /** Em atraso em relação à data prevista. */
    public function getAtrasadaAttribute(): bool
    {
        return $this->status !== StatusInvestigacao::Concluida
            && $this->status !== StatusInvestigacao::Cancelada
            && $this->data_prevista !== null
            && $this->data_prevista->isPast();
    }
}
