<?php

namespace App\Http\Requests;

use App\Enums\CategoriaProfissional;
use App\Enums\FaixaEtaria;
use App\Enums\PapelProfissional;
use App\Enums\SexoPaciente;
use App\Enums\Turno;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação do wizard de notificação de evento adverso (T04/T08).
 */
class EventoAdversoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização tratada pela Policy no controller
    }

    public function rules(): array
    {
        return [
            // Passo 1 — O que aconteceu
            'categoria_evento_id' => ['required', 'integer', Rule::exists('categorias_evento', 'id')->whereNotNull('categoria_pai_id')->where('ativo', true)],
            'setor_ocorrencia_id' => ['required', 'integer', Rule::exists('setores', 'id')->where('ativo', true)],
            'data_ocorrencia' => ['required', 'date', 'before_or_equal:today'],
            'hora_ocorrencia' => ['nullable', 'date_format:H:i'],
            'turno' => ['required', Rule::enum(Turno::class)],
            'local_especifico' => ['nullable', 'string', 'max:150'],

            // Passo 2 — Descrição
            'descricao' => ['required', 'string', 'min:30'],
            'fatores_contribuintes' => ['nullable', 'string', 'max:5000'],
            'acao_imediata' => ['nullable', 'string', 'max:5000'],

            // Passo 3 — Envolvidos
            'paciente_envolvido' => ['sometimes', 'boolean'],
            'paciente_codigo' => ['nullable', 'string', 'max:64'],
            'paciente_faixa_etaria' => ['nullable', Rule::enum(FaixaEtaria::class)],
            'paciente_sexo' => ['nullable', Rule::enum(SexoPaciente::class)],
            'profissionais' => ['nullable', 'array', 'max:10'],
            'profissionais.*.user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:profissionais.*.nome_externo'],
            'profissionais.*.nome_externo' => ['nullable', 'string', 'max:150', 'required_without:profissionais.*.user_id'],
            'profissionais.*.categoria_profissional' => ['required', Rule::enum(CategoriaProfissional::class)],
            'profissionais.*.papel' => ['required', Rule::enum(PapelProfissional::class)],

            // Passo 4 — Revisão e envio
            'anonima' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'categoria_evento_id' => 'categoria do evento',
            'setor_ocorrencia_id' => 'setor de ocorrência',
            'data_ocorrencia' => 'data da ocorrência',
            'hora_ocorrencia' => 'hora da ocorrência',
            'turno' => 'turno',
            'local_especifico' => 'local específico',
            'descricao' => 'descrição do ocorrido',
            'fatores_contribuintes' => 'fatores contribuintes',
            'acao_imediata' => 'ação imediata',
            'paciente_codigo' => 'identificador do paciente',
            'paciente_faixa_etaria' => 'faixa etária',
            'paciente_sexo' => 'sexo do paciente',
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.min' => 'A descrição deve ter no mínimo 30 caracteres — relate o ocorrido com detalhes.',
            'data_ocorrencia.before_or_equal' => 'A data da ocorrência não pode ser futura.',
            'profissionais.*.user_id.required_without' => 'Informe o profissional cadastrado ou o nome externo.',
            'profissionais.*.nome_externo.required_without' => 'Informe o profissional cadastrado ou o nome externo.',
        ];
    }
}
