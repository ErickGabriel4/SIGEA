<?php

namespace Tests\Feature\Eventos;

use App\Enums\CategoriaProfissional;
use App\Enums\PapelProfissional;
use App\Enums\StatusEvento;
use App\Enums\Turno;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Models\Setor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notificação de evento adverso — wizard, rascunho e envio (T04 a T08 — UC02).
 */
class NotificacaoEventoTest extends TestCase
{
    use RefreshDatabase;

    private CategoriaEvento $categoria;

    private Setor $setor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();

        $raiz = CategoriaEvento::factory()->create(['codigo' => 'MED']);
        $this->categoria = CategoriaEvento::factory()->filhaDe($raiz->id)->create(['codigo' => 'MED.01']);
        $this->setor = Setor::factory()->create();
    }

    /** Payload mínimo válido do wizard. */
    private function dadosValidos(array $extras = []): array
    {
        return array_merge([
            'categoria_evento_id' => $this->categoria->id,
            'setor_ocorrencia_id' => $this->setor->id,
            'data_ocorrencia' => today()->subDay()->toDateString(),
            'hora_ocorrencia' => '14:30',
            'turno' => Turno::Tarde->value,
            'local_especifico' => 'Leito 12',
            'descricao' => 'Paciente recebeu dose dobrada de anticoagulante durante a troca de plantão da tarde.',
            'paciente_envolvido' => 1,
        ], $extras);
    }

    public function test_notificador_abre_o_formulario_de_notificacao(): void
    {
        $this->actingAs($this->criarNotificador())->get('/eventos/novo')
            ->assertOk()
            ->assertSee('Passo 1 — O que aconteceu')
            ->assertSee('MED.01');
    }

    public function test_notificacao_enviada_gera_protocolo_e_vai_para_triagem(): void
    {
        $notificador = $this->criarNotificador(['setor_id' => $this->setor->id]);

        $this->actingAs($notificador)
            ->post('/eventos', $this->dadosValidos(['acao_formulario' => 'enviar']))
            ->assertOk()
            ->assertSee('Notificação enviada com sucesso');

        $evento = EventoAdverso::firstOrFail();

        $this->assertSame(StatusEvento::Notificado, $evento->status);
        $this->assertSame($notificador->id, $evento->notificante_id);
        $this->assertSame($this->setor->id, $evento->setor_notificante_id);
        $this->assertStringStartsWith('EA-', $evento->protocolo);
        $this->assertSame('envio', $evento->historico()->latest('id')->first()->acao);
    }

    public function test_rascunho_nao_segue_para_triagem(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->dadosValidos(['acao_formulario' => 'rascunho']))
            ->assertRedirect();

        $this->assertSame(StatusEvento::Rascunho, EventoAdverso::firstOrFail()->status);
    }

    public function test_identificador_do_paciente_nunca_e_gravado_em_claro(): void
    {
        $this->actingAs($this->criarNotificador())
            ->post('/eventos', $this->dadosValidos([
                'acao_formulario' => 'enviar',
                'paciente_codigo' => '338271',
            ]));

        $evento = EventoAdverso::firstOrFail();

        $this->assertStringStartsWith('PAC-', $evento->paciente_codigo);
        $this->assertStringNotContainsString('338271', $evento->paciente_codigo);
    }

    public function test_notificacao_anonima_e_enviada_direto_e_sem_autoria(): void
    {
        $this->actingAs($this->criarNotificador())
            ->post('/eventos', $this->dadosValidos(['anonima' => 1, 'acao_formulario' => 'rascunho']))
            ->assertOk()
            ->assertSee('Notificação anônima');

        $evento = EventoAdverso::firstOrFail();

        $this->assertTrue($evento->anonima);
        $this->assertNull($evento->notificante_id);
        $this->assertNull($evento->setor_notificante_id);
        $this->assertSame(StatusEvento::Notificado, $evento->status);
    }

    public function test_profissionais_envolvidos_sao_registrados(): void
    {
        $colega = $this->criarNotificador();

        $this->actingAs($this->criarNotificador())
            ->post('/eventos', $this->dadosValidos([
                'acao_formulario' => 'enviar',
                'profissionais' => [
                    ['user_id' => $colega->id, 'categoria_profissional' => CategoriaProfissional::Enfermeiro->value, 'papel' => PapelProfissional::Envolvido->value],
                    ['nome_externo' => 'Equipe terceirizada', 'categoria_profissional' => CategoriaProfissional::Outro->value, 'papel' => PapelProfissional::Testemunha->value],
                ],
            ]));

        $this->assertSame(2, EventoAdverso::firstOrFail()->profissionais()->count());
    }

    public function test_descricao_curta_e_data_futura_sao_recusadas(): void
    {
        $this->actingAs($this->criarNotificador())
            ->post('/eventos', $this->dadosValidos([
                'descricao' => 'Caiu.',
                'data_ocorrencia' => today()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['descricao', 'data_ocorrencia']);

        $this->assertSame(0, EventoAdverso::count());
    }

    public function test_categoria_raiz_nao_pode_ser_escolhida_como_tipo_do_evento(): void
    {
        $raiz = CategoriaEvento::factory()->create(['codigo' => 'QUE']);

        $this->actingAs($this->criarNotificador())
            ->post('/eventos', $this->dadosValidos(['categoria_evento_id' => $raiz->id]))
            ->assertSessionHasErrors('categoria_evento_id');
    }

    public function test_dono_edita_envia_e_exclui_o_proprio_rascunho(): void
    {
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->rascunho()->create(['notificante_id' => $notificador->id]);

        $this->actingAs($notificador)->get(route('eventos.edit', $evento))->assertOk();

        $this->actingAs($notificador)
            ->post(route('eventos.enviar', $evento))
            ->assertOk()
            ->assertSee($evento->protocolo);

        $this->assertSame(StatusEvento::Notificado, $evento->fresh()->status);

        // Já enviado: não é mais editável nem excluível
        $this->actingAs($notificador)->get(route('eventos.edit', $evento))->assertForbidden();
        $this->actingAs($notificador)->delete(route('eventos.destroy', $evento))->assertForbidden();
    }

    public function test_rascunho_de_outro_notificador_e_inacessivel(): void
    {
        $evento = EventoAdverso::factory()->rascunho()->create();

        $this->actingAs($this->criarNotificador())
            ->get(route('eventos.show', $evento))
            ->assertForbidden();
    }

    public function test_notificador_ve_apenas_as_proprias_notificacoes(): void
    {
        $notificador = $this->criarNotificador();
        $meu = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);
        $alheio = EventoAdverso::factory()->create();
        $anonimo = EventoAdverso::factory()->anonima()->create();

        $this->actingAs($notificador)->get('/eventos/meus')
            ->assertOk()
            ->assertSee($meu->protocolo)
            ->assertDontSee($alheio->protocolo)
            ->assertDontSee($anonimo->protocolo);

        $this->actingAs($notificador)->get('/eventos')
            ->assertOk()
            ->assertSee($meu->protocolo)
            ->assertDontSee($alheio->protocolo);
    }

    public function test_gestor_ve_todos_os_eventos_e_filtra_por_setor(): void
    {
        $outroSetor = Setor::factory()->create();
        $daUti = EventoAdverso::factory()->create(['setor_ocorrencia_id' => $this->setor->id]);
        $doCentroCirurgico = EventoAdverso::factory()->create(['setor_ocorrencia_id' => $outroSetor->id]);

        $gestor = $this->criarGestor();

        $this->actingAs($gestor)->get('/eventos')
            ->assertOk()
            ->assertSee($daUti->protocolo)
            ->assertSee($doCentroCirurgico->protocolo);

        $this->actingAs($gestor)->get('/eventos?setor_id=' . $this->setor->id)
            ->assertOk()
            ->assertSee($daUti->protocolo)
            ->assertDontSee($doCentroCirurgico->protocolo);
    }

    public function test_gestor_abre_o_detalhe_com_resumo_e_historico(): void
    {
        $evento = EventoAdverso::factory()->create(['local_especifico' => 'Sala de medicação']);

        $this->actingAs($this->criarGestor())->get(route('eventos.show', $evento))
            ->assertOk()
            ->assertSee($evento->protocolo)
            ->assertSee('Sala de medicação')
            ->assertSee('Histórico');
    }
}
