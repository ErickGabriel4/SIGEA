<?php

namespace App\Http\Requests;

use App\Enums\ClassificacaoIncidente;
use App\Enums\Evitabilidade;
use App\Enums\GrauDano;
use App\Enums\Prioridade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação do formulário de triagem/classificação (T10).
 */
class TriagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('evento.classificar');
    }

    public function rules(): array
    {
        return [
            'categoria_evento_id' => ['required', 'integer', Rule::exists('categorias_evento', 'id')->whereNotNull('categoria_pai_id')->where('ativo', true)],
            'classificacao' => ['required', Rule::enum(ClassificacaoIncidente::class)],
            'grau_dano' => ['required', Rule::enum(GrauDano::class)],
            'evitabilidade' => ['required', Rule::enum(Evitabilidade::class), Rule::notIn(['nao_avaliado'])],
            'probabilidade' => ['required', 'integer', 'between:1,5'],
            'severidade' => ['required', 'integer', 'between:1,5'],
            'prioridade' => ['nullable', Rule::enum(Prioridade::class)],
            'prazo_conclusao' => ['nullable', 'date', 'after_or_equal:today'],
            'feedback_notificante' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_evento_id' => 'categoria do evento',
            'classificacao' => 'classificação do incidente',
            'grau_dano' => 'grau de dano',
            'evitabilidade' => 'evitabilidade',
            'probabilidade' => 'probabilidade',
            'severidade' => 'severidade',
            'prioridade' => 'prioridade',
            'prazo_conclusao' => 'prazo de conclusão',
            'feedback_notificante' => 'devolutiva ao notificante',
        ];
    }

    public function messages(): array
    {
        return [
            'evitabilidade.not_in' => 'A evitabilidade deve ser avaliada na triagem.',
            'prazo_conclusao.after_or_equal' => 'O prazo de conclusão não pode estar no passado.',
        ];
    }
}
