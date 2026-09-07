<?php

namespace App\Http\Requests;

use App\Enums\MetodoInvestigacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de criação/edição de investigação (T12).
 */
class InvestigacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('investigacao.gerenciar');
    }

    public function rules(): array
    {
        return [
            'responsavel_id' => ['required', 'integer', 'exists:users,id'],
            'metodo' => ['required', Rule::enum(MetodoInvestigacao::class)],
            'data_inicio' => ['required', 'date', 'before_or_equal:today'],
            'data_prevista' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'descricao_analise' => ['nullable', 'string', 'max:10000'],
            'causa_raiz' => ['nullable', 'string', 'max:5000'],
            'conclusao' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'responsavel_id' => 'responsável',
            'metodo' => 'método de análise',
            'data_inicio' => 'data de início',
            'data_prevista' => 'data prevista',
            'descricao_analise' => 'desenvolvimento da análise',
            'causa_raiz' => 'causa raiz',
            'conclusao' => 'conclusão',
        ];
    }
}
