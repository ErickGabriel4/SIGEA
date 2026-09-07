<?php

namespace App\Models;

use App\Enums\CategoriaCausa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Causa identificada na investigação (eixo de Ishikawa ou elo dos 5 Porquês).
 */
class InvestigacaoCausa extends Model
{
    use HasFactory;

    protected $table = 'investigacao_causas';

    protected $fillable = [
        'investigacao_id',
        'categoria',
        'descricao',
        'ordem',
        'eh_causa_raiz',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => CategoriaCausa::class,
            'ordem' => 'integer',
            'eh_causa_raiz' => 'boolean',
        ];
    }

    public function investigacao(): BelongsTo
    {
        return $this->belongsTo(Investigacao::class);
    }
}
