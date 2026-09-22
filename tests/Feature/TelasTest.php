<?php

namespace Tests\Feature;

use App\Enums\StatusEvento;
use App\Models\AcaoCorretiva;
use App\Models\Configuracao;
use App\Models\EventoAdverso;
use App\Models\Investigacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Teste de fumaça: todas as telas principais renderizam sem erro (T01–T23).
 */
class TelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();

        Configuracao::create([
            'chave' => 'nome_instituicao', 'valor' => 'Hospital Teste',
            'tipo' => 'string', 'grupo' => 'instituicao',
        ]);
    }

    public function test_todas_as_telas_principais_renderizam(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $admin = $this->criarAdministrador();
        $gestor = $this->criarGestor();
        $notificador = $this->criarNotificador();

        $evento = EventoAdverso::factory()->classificado()->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'status' => StatusEvento::EmInvestigacao,
            'gestor_responsavel_id' => $gestor->id,
        ]);
        $investigacao = Investigacao::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $gestor->id,
        ]);
        $acao = AcaoCorretiva::factory()->create([
            'evento_adverso_id' => $evento->id,
            'responsavel_id' => $notificador->id,
        ]);
        $rascunho = EventoAdverso::factory()->rascunho()->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        // Convidado
        $this->get('/login')->assertOk();
        $this->get('/esqueci-senha')->assertOk();

        // Notificador
        foreach (['/eventos/meus', '/eventos/novo', '/acoes/minhas', '/notificacoes', '/perfil'] as $url) {
            $this->actingAs($notificador)->get($url)->assertOk();
        }
        $this->actingAs($notificador)->get(route('eventos.edit', $rascunho))->assertOk();
        $this->actingAs($notificador)->get(route('eventos.show', $evento))->assertOk();

        // Gestor
        foreach ([
            '/painel', '/eventos', '/triagem', '/investigacoes', '/acoes',
            '/relatorios', '/relatorios/consolidado', '/relatorios/por-setor',
            '/relatorios/por-categoria', '/relatorios/acoes-pendentes',
        ] as $url) {
            $this->actingAs($gestor)->get($url)->assertOk();
        }
        $this->actingAs($gestor)->get(route('investigacoes.show', $investigacao))->assertOk();
        $this->actingAs($gestor)->get(route('investigacoes.edit', $investigacao))->assertOk();
        $this->actingAs($gestor)->get(route('acoes.edit', $acao))->assertOk();
        $this->actingAs($gestor)->get(route('acoes.create', $evento))->assertOk();

        // Administrador
        foreach ([
            '/admin/usuarios', '/admin/usuarios/create',
            '/admin/setores', '/admin/setores/create',
            '/admin/categorias', '/admin/categorias/create',
            '/admin/configuracoes', '/admin/auditoria',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.usuarios.edit', $notificador))->assertOk();
    }

    public function test_ficha_do_evento_em_pdf_e_gerada(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $resposta = $this->actingAs($gestor)->get(route('eventos.pdf', $evento));

        $resposta->assertOk();
        $this->assertSame('application/pdf', $resposta->headers->get('content-type'));
    }

    public function test_exportacao_excel_e_gerada(): void
    {
        [$setor, $categoria] = $this->criarBaseEvento();
        $gestor = $this->criarGestor();

        EventoAdverso::factory()->create([
            'notificante_id' => $this->criarNotificador()->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->actingAs($gestor)
            ->get(route('eventos.exportar'))
            ->assertOk()
            ->assertDownload();
    }
}
