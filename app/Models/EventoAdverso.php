<?php

namespace App\Models;

use App\Enums\ClassificacaoIncidente;
use App\Enums\Evitabilidade;
use App\Enums\FaixaEtaria;
use App\Enums\GrauDano;
use App\Enums\NivelRisco;
use App\Enums\Prioridade;
use App\Enums\SexoPaciente;
use App\Enums\StatusEvento;
use App\Enums\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Evento adverso — entidade central do SIGEA.
 */
class EventoAdverso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventos_adversos';

    protected $fillable = [
        'notificante_id',
        'anonima',
        'categoria_evento_id',
        'setor_ocorrencia_id',
        'setor_notificante_id',
        'data_ocorrencia',
        'hora_ocorrencia',
        'turno',
        'local_especifico',
        'descricao',
        'fatores_contribuintes',
        'acao_imediata',
        'paciente_envolvido',
        'paciente_codigo',
        'paciente_faixa_etaria',
        'paciente_sexo',
        'classificacao',
        'grau_dano',
        'evitabilidade',
        'probabilidade',
        'severidade',
        'nivel_risco',
        'status',
        'prioridade',
        'gestor_responsavel_id',
        'prazo_conclusao',
        'feedback_notificante',
        'notificavel_anvisa',
        'notificado_anvisa_em',
        'protocolo_notivisa',
        'triado_em',
        'investigado_em',
        'concluido_em',
    ];

    protected function casts(): array
    {
        return [
            'anonima' => 'boolean',
            'paciente_envolvido' => 'boolean',
            'notificavel_anvisa' => 'boolean',
            'data_ocorrencia' => 'date',
            'prazo_conclusao' => 'date',
            'notificado_anvisa_em' => 'date',
            'triado_em' => 'datetime',
            'investigado_em' => 'datetime',
            'concluido_em' => 'datetime',
            'probabilidade' => 'integer',
            'severidade' => 'integer',
            'score_risco' => 'integer',
            'turno' => Turno::class,
            'paciente_faixa_etaria' => FaixaEtaria::class,
            'paciente_sexo' => SexoPaciente::class,
            'classificacao' => ClassificacaoIncidente::class,
            'grau_dano' => GrauDano::class,
            'evitabilidade' => Evitabilidade::class,
            'nivel_risco' => NivelRisco::class,
            'status' => StatusEvento::class,
            'prioridade' => Prioridade::class,
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────

    public function notificante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notificante_id');
    }

    public function gestorResponsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_responsavel_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEvento::class, 'categoria_evento_id');
    }

    public function setorOcorrencia(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_ocorrencia_id');
    }

    public function setorNotificante(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_notificante_id');
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(EventoProfissional::class);
    }

    public function historico(): HasMany
    {
        return $this->hasMany(EventoHistorico::class)->orderBy('created_at');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(EventoAnexo::class);
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel')->orderByDesc('created_at');
    }

    public function investigacao(): HasOne
    {
        return $this->hasOne(Investigacao::class);
    }

    public function acoesCorretivas(): HasMany
    {
        return $this->hasMany(AcaoCorretiva::class);
    }

    // ── Escopos ────────────────────────────────────────────────────────

    /** Eventos visíveis a um usuário conforme seu perfil (regra de propriedade). */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        if ($usuario->can('evento.ver_todos')) {
            return $query;
        }

        return $query->where('notificante_id', $usuario->id)->where('anonima', false);
    }

    public function scopePendentesTriagem(Builder $query): Builder
    {
        return $query->where('status', StatusEvento::Notificado);
    }

    public function scopeNoPeriodo(Builder $query, ?string $inicio, ?string $fim): Builder
    {
        return $query
            ->when($inicio, fn ($q) => $q->whereDate('data_ocorrencia', '>=', $inicio))
            ->when($fim, fn ($q) => $q->whereDate('data_ocorrencia', '<=', $fim));
    }

    // ── Acessores auxiliares ───────────────────────────────────────────

    /** Classificação completa: pré-condição para encaminhar a triagem. */
    public function classificacaoCompleta(): bool
    {
        return $this->categoria_evento_id !== null
            && $this->classificacao !== null
            && $this->grau_dano !== null
            && $this->evitabilidade !== null
            && $this->evitabilidade !== Evitabilidade::NaoAvaliado
            && $this->probabilidade !== null
            && $this->severidade !== null;
    }

    /** Situação do prazo: verde (no prazo), amarelo (próximo), vermelho (estourado). */
    public function getSituacaoPrazoAttribute(): ?string
    {
        if ($this->prazo_conclusao === null
            || $this->status === StatusEvento::Concluido
            || $this->status === StatusEvento::Cancelado) {
            return null;
        }

        $dias = (int) Configuracao::obter('dias_alerta_prazo', 7);

        if ($this->prazo_conclusao->isPast()) {
            return 'vermelho';
        }

        return $this->prazo_conclusao->diffInDays(now()) <= $dias ? 'amarelo' : 'verde';
    }

    /** Pendente de registro do protocolo NOTIVISA. */
    public function pendenteNotivisa(): bool
    {
        return $this->notificavel_anvisa && blank($this->protocolo_notivisa);
    }
}
