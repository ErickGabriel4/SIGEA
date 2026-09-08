<?php

namespace App\Http\Requests;

use App\Enums\TipoAcao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de cadastro/edição de ação corretiva.
 */
class AcaoCorretivaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('acao.gerenciar');
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:180'],
            'descricao' => ['required', 'string', 'max:5000'],
            'tipo' => ['required', Rule::enum(TipoAcao::class)],
            'responsavel_id' => ['required', 'integer', Rule::exists('users', 'id')->where('ativo', true)],
            'setor_id' => ['nullable', 'integer', 'exists:setores,id'],
            'prazo' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'descricao' => 'descrição',
            'tipo' => 'tipo da ação',
            'responsavel_id' => 'responsável',
            'setor_id' => 'setor',
            'prazo' => 'prazo',
        ];
    }

    public function messages(): array
    {
        return [
            'prazo.after_or_equal' => 'O prazo da ação não pode estar no passado.',
        ];
    }
}
