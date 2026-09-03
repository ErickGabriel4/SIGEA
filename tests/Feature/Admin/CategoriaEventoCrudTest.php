<?php

namespace Tests\Feature\Admin;

use App\Models\CategoriaEvento;
use Database\Seeders\CategoriaEventoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cadastro da taxonomia de categorias de evento (T19 — UC19).
 */
class CategoriaEventoCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_administrador_lista_a_arvore_de_categorias(): void
    {
        $raiz = CategoriaEvento::factory()->create(['codigo' => 'MED', 'nome' => 'Medicação']);
        CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'MED.01', 'nome' => 'Dose incorreta']);

        $this->actingAs($this->criarAdministrador())->get('/admin/categorias')
            ->assertOk()
            ->assertSee('Medicação')
            ->assertSee('Dose incorreta');
    }

    public function test_administrador_cadastra_categoria_raiz(): void
    {
        $this->actingAs($this->criarAdministrador())
            ->post('/admin/categorias', [
                'codigo' => 'QUE',
                'nome' => 'Queda',
                'codigo_icps' => 'ICPS-10.3',
                'notificavel_anvisa' => 0,
                'exige_investigacao' => 1,
                'ativo' => 1,
            ])
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias_evento', [
            'codigo' => 'QUE',
            'categoria_pai_id' => null,
            'exige_investigacao' => true,
        ]);
    }

    public function test_subcategoria_so_pode_pendurar_em_categoria_raiz(): void
    {
        $raiz = CategoriaEvento::factory()->create(['codigo' => 'CIR']);
        $filha = CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'CIR.01']);

        $this->actingAs($this->criarAdministrador())
            ->post('/admin/categorias', [
                'codigo' => 'CIR.01.01',
                'nome' => 'Terceiro nível',
                'categoria_pai_id' => $filha->id,
            ])
            ->assertSessionHasErrors('categoria_pai_id');
    }

    public function test_categoria_raiz_com_filhas_nao_vira_subcategoria(): void
    {
        $raiz = CategoriaEvento::factory()->create(['codigo' => 'LAB']);
        CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'LAB.01']);
        $outraRaiz = CategoriaEvento::factory()->create(['codigo' => 'DOC']);

        $this->actingAs($this->criarAdministrador())
            ->put(route('admin.categorias.update', $raiz), [
                'codigo' => 'LAB',
                'nome' => 'Laboratório',
                'categoria_pai_id' => $outraRaiz->id,
            ])
            ->assertSessionHas('erro');

        $this->assertDatabaseHas('categorias_evento', ['id' => $raiz->id, 'categoria_pai_id' => null]);
    }

    public function test_excluir_raiz_arrasta_as_subcategorias(): void
    {
        $raiz = CategoriaEvento::factory()->create(['codigo' => 'OXI']);
        $filha = CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'OXI.01']);

        $this->actingAs($this->criarAdministrador())
            ->delete(route('admin.categorias.destroy', $raiz))
            ->assertRedirect(route('admin.categorias.index'));

        $this->assertSoftDeleted('categorias_evento', ['id' => $raiz->id]);
        $this->assertSoftDeleted('categorias_evento', ['id' => $filha->id]);
    }

    public function test_gestor_nao_acessa_o_cadastro_de_categorias(): void
    {
        $this->actingAs($this->criarGestor())->get('/admin/categorias')->assertForbidden();
    }

    public function test_seeder_carrega_a_taxonomia_icps_completa(): void
    {
        $this->seed(CategoriaEventoSeeder::class);

        $this->assertSame(13, CategoriaEvento::raizes()->count());
        $this->assertDatabaseHas('categorias_evento', ['codigo' => 'MED.01', 'nome' => 'Dose incorreta']);
        $this->assertDatabaseHas('categorias_evento', ['codigo' => 'CIR', 'notificavel_anvisa' => true]);
    }
}
