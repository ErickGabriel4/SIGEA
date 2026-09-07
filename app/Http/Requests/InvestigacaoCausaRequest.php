<?php

namespace App\Http\Requests;

use App\Enums\CategoriaCausa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de causas da investigação (Ishikawa / 5 Porquês).
 */
class InvestigacaoCausaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('investigacao.gerenciar');
    }

    public function rules(): array
    {
        return [
            'categoria' => ['required', Rule::enum(CategoriaCausa::class)],
            'descricao' => ['required', 'string', 'max:2000'],
            'ordem' => ['nullable', 'integer', 'between:1,20'],
            'eh_causa_raiz' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria' => 'eixo da causa',
            'descricao' => 'descrição da causa',
            'ordem' => 'ordem',
            'eh_causa_raiz' => 'causa raiz',
        ];
    }
}
