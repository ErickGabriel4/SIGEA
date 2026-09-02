<?php

namespace Tests\Feature\Admin;

use App\Enums\TipoSetor;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cadastro de setores hospitalares (T18 — UC19).
 */
class SetorCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_administrador_lista_e_abre_o_formulario_de_setor(): void
    {
        $administrador = $this->criarAdministrador();
        $setor = Setor::factory()->create(['nome' => 'Unidade de Terapia Intensiva']);

        $this->actingAs($administrador)->get('/admin/setores')
            ->assertOk()
            ->assertSee('Unidade de Terapia Intensiva');

        $this->actingAs($administrador)->get('/admin/setores/create')->assertOk();
        $this->actingAs($administrador)->get(route('admin.setores.edit', $setor))->assertOk();
    }

    public function test_administrador_cadastra_setor(): void
    {
        $this->actingAs($this->criarAdministrador())
            ->post('/admin/setores', [
                'nome' => 'Centro Cirúrgico',
                'sigla' => 'CC',
                'tipo' => TipoSetor::Cirurgico->value,
                'leitos' => 12,
                'ativo' => 1,
            ])
            ->assertRedirect(route('admin.setores.index'))
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('setores', ['sigla' => 'CC', 'leitos' => 12, 'ativo' => true]);
    }

    public function test_sigla_duplicada_e_rejeitada(): void
    {
        Setor::factory()->create(['sigla' => 'UTI']);

        $this->actingAs($this->criarAdministrador())
            ->post('/admin/setores', [
                'nome' => 'Outra UTI',
                'sigla' => 'UTI',
                'tipo' => TipoSetor::Uti->value,
            ])
            ->assertSessionHasErrors('sigla');
    }

    public function test_administrador_edita_setor(): void
    {
        $setor = Setor::factory()->create(['nome' => 'Pronto Socorro', 'leitos' => 10]);

        $this->actingAs($this->criarAdministrador())
            ->put(route('admin.setores.update', $setor), [
                'nome' => 'Pronto Socorro Adulto',
                'sigla' => $setor->sigla,
                'tipo' => $setor->tipo->value,
                'leitos' => 20,
                'ativo' => 1,
            ])
            ->assertRedirect(route('admin.setores.index'));

        $this->assertDatabaseHas('setores', ['id' => $setor->id, 'nome' => 'Pronto Socorro Adulto', 'leitos' => 20]);
    }

    public function test_setor_sem_uso_e_excluido_logicamente(): void
    {
        $setor = Setor::factory()->create();

        $this->actingAs($this->criarAdministrador())
            ->delete(route('admin.setores.destroy', $setor))
            ->assertRedirect(route('admin.setores.index'));

        $this->assertSoftDeleted('setores', ['id' => $setor->id]);
    }

    public function test_setor_com_usuarios_lotados_nao_e_excluido(): void
    {
        $setor = Setor::factory()->create();
        User::factory()->create(['setor_id' => $setor->id]);

        $this->actingAs($this->criarAdministrador())
            ->delete(route('admin.setores.destroy', $setor))
            ->assertSessionHas('erro');

        $this->assertNotSoftDeleted('setores', ['id' => $setor->id]);
    }

    public function test_gestor_e_notificador_nao_acessam_o_cadastro_de_setores(): void
    {
        $this->actingAs($this->criarGestor())->get('/admin/setores')->assertForbidden();
        $this->actingAs($this->criarNotificador())->get('/admin/setores')->assertForbidden();
    }
}
