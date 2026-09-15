<?php

namespace App\Models;

use App\Enums\TipoConfiguracao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Parâmetro de configuração do sistema (SLAs, instituição, regras).
 */
class Configuracao extends Model
{
    use LogsActivity;

    protected $table = 'configuracoes';

    protected $fillable = [
        'chave',
        'valor',
        'tipo',
        'grupo',
        'descricao',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoConfiguracao::class,
        ];
    }

    /**
     * Obtém o valor tipado de um parâmetro, com cache de 10 minutos.
     *
     * Só valores escalares vão para o cache: guardar o próprio model quebra
     * nos drivers que serializam (banco, arquivo, Redis).
     */
    public static function obter(string $chave, mixed $padrao = null): mixed
    {
        $config = Cache::remember("config.{$chave}", now()->addMinutes(10), function () use ($chave) {
            $registro = DB::table('configuracoes')->where('chave', $chave)->first(['valor', 'tipo']);

            return $registro === null ? [] : (array) $registro;
        });

        if ($config === []) {
            return $padrao;
        }

        return match (TipoConfiguracao::tryFrom($config['tipo'])) {
            TipoConfiguracao::Inteiro => (int) $config['valor'],
            TipoConfiguracao::Booleano => filter_var($config['valor'], FILTER_VALIDATE_BOOLEAN),
            TipoConfiguracao::Json => json_decode($config['valor'], true),
            default => $config['valor'],
        };
    }

    /**
     * Define o valor de um parâmetro e invalida o cache.
     */
    public static function definir(string $chave, mixed $valor): void
    {
        static::where('chave', $chave)->update([
            'valor' => is_array($valor) ? json_encode($valor) : (string) $valor,
        ]);

        Cache::forget("config.{$chave}");
    }

    protected static function booted(): void
    {
        static::saved(fn (self $config) => Cache::forget("config.{$config->chave}"));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['chave', 'valor'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('configuracao');
    }
}
