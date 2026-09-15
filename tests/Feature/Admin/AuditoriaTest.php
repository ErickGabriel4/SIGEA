<?php

namespace Tests\Feature\Admin;

use App\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Trilha de auditoria com diff de alterações (T21 — UC21, RF12).
 */
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_alteracao_de_setor_gera_registro_de_auditoria_com_causador(): void
    {
        $administrador = $this->criarAdministrador();
        $setor = Setor::factory()->create(['nome' => 'Pronto Socorro', 'leitos' => 10]);

        $this->actingAs($administrador)->put(route('admin.setores.update', $setor), [
            'nome' => 'Pronto Socorro Adulto',
            'sigla' => $setor->sigla,
            'tipo' => $setor->tipo->value,
            'leitos' => 20,
            'ativo' => 1,
        ]);

        $registro = Activity::where('log_name', 'setor')
            ->where('subject_id', $setor->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame($administrador->id, $registro->causer_id);
        $this->assertSame(10, $registro->properties['old']['leitos']);
        $this->assertSame(20, $registro->properties['attributes']['leitos']);
    }

    public function test_administrador_lista_e_filtra_a_trilha_de_auditoria(): void
    {
        $administrador = $this->criarAdministrador();
        $setor = Setor::factory()->create(['leitos' => 10]);
        $setor->update(['leitos' => 15]);

        $this->actingAs($administrador)->get('/admin/auditoria')
            ->assertOk()
            ->assertSee('setor');

        $this->actingAs($administrador)->get('/admin/auditoria?' . http_build_query(['log' => 'setor']))
            ->assertOk()
            ->assertSee('setor');

        $this->actingAs($administrador)->get('/admin/auditoria?' . http_build_query(['log' => 'categoria_evento']))
            ->assertOk()
            ->assertSee('Nenhum registro de auditoria.');
    }

    public function test_administrador_ve_o_diff_do_registro(): void
    {
        $administrador = $this->criarAdministrador();
        $setor = Setor::factory()->create(['leitos' => 10]);
        $setor->update(['leitos' => 30]);

        $registro = Activity::where('log_name', 'setor')
            ->where('subject_id', $setor->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->actingAs($administrador)->get(route('admin.auditoria.show', $registro))
            ->assertOk()
            ->assertSee('leitos')
            ->assertSee('10')
            ->assertSee('30');
    }

    public function test_gestor_e_notificador_nao_acessam_a_auditoria(): void
    {
        $this->actingAs($this->criarGestor())->get('/admin/auditoria')->assertForbidden();
        $this->actingAs($this->criarNotificador())->get('/admin/auditoria')->assertForbidden();
    }
}
