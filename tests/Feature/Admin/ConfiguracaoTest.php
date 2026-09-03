<?php

namespace Tests\Feature\Admin;

use App\Models\Configuracao;
use Database\Seeders\ConfiguracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parâmetros configuráveis do sistema (T20 — UC20).
 */
class ConfiguracaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        $this->seed(ConfiguracaoSeeder::class);
    }

    public function test_administrador_abre_a_tela_de_configuracoes_agrupada(): void
    {
        $this->actingAs($this->criarAdministrador())->get('/admin/configuracoes')
            ->assertOk()
            ->assertSee('Prazos (SLA)')
            ->assertSee('sla_triagem_horas');
    }

    public function test_administrador_altera_parametros(): void
    {
        $this->actingAs($this->criarAdministrador())
            ->put('/admin/configuracoes', [
                'configuracoes' => [
                    'sla_triagem_horas' => '24',
                    'nome_instituicao' => 'Hospital Escola do IFSP',
                    'permite_notificacao_anonima' => '0',
                ],
            ])
            ->assertSessionHas('sucesso');

        $this->assertSame(24, Configuracao::obter('sla_triagem_horas'));
        $this->assertSame('Hospital Escola do IFSP', Configuracao::obter('nome_instituicao'));
        $this->assertFalse(Configuracao::obter('permite_notificacao_anonima'));
    }

    public function test_valor_nao_numerico_nao_sobrescreve_parametro_inteiro(): void
    {
        $this->actingAs($this->criarAdministrador())
            ->put('/admin/configuracoes', [
                'configuracoes' => ['sla_investigacao_dias' => 'trinta'],
            ]);

        $this->assertSame(30, Configuracao::obter('sla_investigacao_dias'));
    }

    public function test_obter_devolve_o_padrao_quando_a_chave_nao_existe(): void
    {
        $this->assertSame('padrao', Configuracao::obter('chave_inexistente', 'padrao'));
    }

    public function test_gestor_nao_acessa_as_configuracoes(): void
    {
        $this->actingAs($this->criarGestor())->get('/admin/configuracoes')->assertForbidden();
    }
}
