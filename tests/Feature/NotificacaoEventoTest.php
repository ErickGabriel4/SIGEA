<?php

namespace Tests\Feature;

use App\Enums\StatusEvento;
use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Notificação de eventos: rascunho, envio, protocolo e anonimato (UC02 / Seção 11).
 */
class NotificacaoEventoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Notification::fake();
    }

    /** Payload mínimo válido do wizard. */
    private function payload(array $extras = []): array
    {
        [$setor, $categoria] = $this->criarBaseEvento();

        return array_merge([
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
            'data_ocorrencia' => today()->toDateString(),
            'turno' => 'manha',
            'descricao' => 'Descrição detalhada do evento adverso simulado para fins de teste automatizado.',
        ], $extras);
    }

    public function test_criacao_de_rascunho_nao_envia(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->payload(['acao_formulario' => 'rascunho']))
            ->assertRedirect();

        $evento = EventoAdverso::first();
        $this->assertSame(StatusEvento::Rascunho, $evento->status);
        $this->assertSame($notificador->id, $evento->notificante_id);
    }

    public function test_envio_gera_protocolo_no_formato_correto(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->payload(['acao_formulario' => 'enviar']))
            ->assertOk();

        $evento = EventoAdverso::first();
        $this->assertSame(StatusEvento::Notificado, $evento->status);
        $this->assertMatchesRegularExpression('/^EA-\d{4}-\d{6}$/', $evento->protocolo);
    }

    public function test_protocolos_sao_unicos_e_sequenciais(): void
    {
        $notificador = $this->criarNotificador();
        [$setor, $categoria] = $this->criarBaseEvento();

        EventoAdverso::factory()->count(20)->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $protocolos = EventoAdverso::pluck('protocolo');
        $this->assertSame($protocolos->count(), $protocolos->unique()->count());
    }

    public function test_descricao_curta_e_rejeitada(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->payload(['descricao' => 'Muito curta']))
            ->assertSessionHasErrors('descricao');
    }

    public function test_data_futura_e_rejeitada(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->payload(['data_ocorrencia' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors('data_ocorrencia');
    }

    public function test_notificacao_anonima_nao_grava_vinculo_com_notificante(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)
            ->post('/eventos', $this->payload(['anonima' => 1]))
            ->assertOk();

        $evento = EventoAdverso::first();
        $this->assertTrue($evento->anonima);
        $this->assertNull($evento->notificante_id);
        $this->assertSame(StatusEvento::Notificado, $evento->status);

        // Histórico sem autoria
        $this->assertNull($evento->historico()->first()->user_id);

        // Não aparece em "Minhas notificações"
        $this->actingAs($notificador)
            ->get('/eventos/meus')
            ->assertDontSee($evento->protocolo);
    }

    public function test_paciente_codigo_e_pseudonimizado(): void
    {
        $notificador = $this->criarNotificador();

        $this->actingAs($notificador)->post('/eventos', $this->payload([
            'acao_formulario' => 'enviar',
            'paciente_codigo' => 'PRONTUARIO-12345',
        ]));

        $evento = EventoAdverso::first();
        $this->assertStringStartsWith('PAC-', $evento->paciente_codigo);
        $this->assertStringNotContainsString('12345', $evento->paciente_codigo);
    }

    public function test_dono_edita_e_exclui_apenas_rascunho(): void
    {
        $notificador = $this->criarNotificador();
        [$setor, $categoria] = $this->criarBaseEvento();

        $rascunho = EventoAdverso::factory()->rascunho()->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);
        $enviado = EventoAdverso::factory()->create([
            'notificante_id' => $notificador->id,
            'categoria_evento_id' => $categoria->id,
            'setor_ocorrencia_id' => $setor->id,
        ]);

        $this->actingAs($notificador)->get(route('eventos.edit', $rascunho))->assertOk();
        $this->actingAs($notificador)->get(route('eventos.edit', $enviado))->assertForbidden();
        $this->actingAs($notificador)->delete(route('eventos.destroy', $enviado))->assertForbidden();
        $this->actingAs($notificador)->delete(route('eventos.destroy', $rascunho))->assertRedirect();
        $this->assertSoftDeleted('eventos_adversos', ['id' => $rascunho->id]);
    }
}
