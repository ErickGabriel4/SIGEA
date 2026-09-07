<?php

namespace Tests\Feature\Eventos;

use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comentários no evento — visibilidade de comentário interno por perfil.
 */
class ComentarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
    }

    public function test_notificador_comenta_o_proprio_evento(): void
    {
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $this->actingAs($notificador)
            ->post(route('eventos.comentario.store', $evento), ['corpo' => 'Acompanhando o caso.'])
            ->assertRedirect();

        $comentario = $evento->comentarios()->first();
        $this->assertNotNull($comentario);
        $this->assertSame($notificador->id, $comentario->user_id);
        $this->assertFalse($comentario->interno);
    }

    public function test_notificador_nao_marca_comentario_como_interno(): void
    {
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $this->actingAs($notificador)->post(route('eventos.comentario.store', $evento), [
            'corpo' => 'Tentando marcar como interno.',
            'interno' => true,
        ]);

        $this->assertFalse($evento->comentarios()->first()->interno);
    }

    public function test_gestor_marca_comentario_como_interno(): void
    {
        $gestor = $this->criarGestor();
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $this->actingAs($gestor)->post(route('eventos.comentario.store', $evento), [
            'corpo' => 'Nota interna da triagem.',
            'interno' => true,
        ]);

        $this->assertTrue($evento->comentarios()->first()->interno);
    }

    public function test_notificador_nao_ve_comentario_interno_na_listagem(): void
    {
        $gestor = $this->criarGestor();
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $evento->comentarios()->create(['user_id' => $gestor->id, 'corpo' => 'Nota interna.', 'interno' => true]);
        $evento->comentarios()->create(['user_id' => $gestor->id, 'corpo' => 'Nota pública.', 'interno' => false]);

        $visiveis = $evento->comentarios()->visiveisPara($notificador)->get();

        $this->assertCount(1, $visiveis);
        $this->assertSame('Nota pública.', $visiveis->first()->corpo);
    }

    public function test_gestor_ve_todos_os_comentarios(): void
    {
        $gestor = $this->criarGestor();
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $evento->comentarios()->create(['user_id' => $gestor->id, 'corpo' => 'Nota interna.', 'interno' => true]);
        $evento->comentarios()->create(['user_id' => $gestor->id, 'corpo' => 'Nota pública.', 'interno' => false]);

        $visiveis = $evento->comentarios()->visiveisPara($gestor)->get();

        $this->assertCount(2, $visiveis);
    }

    public function test_intruso_nao_comenta_evento_de_outro_notificador(): void
    {
        $dono = $this->criarNotificador();
        $intruso = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $dono->id]);

        $this->actingAs($intruso)
            ->post(route('eventos.comentario.store', $evento), ['corpo' => 'Não deveria poder.'])
            ->assertForbidden();
    }

    public function test_corpo_em_branco_e_rejeitado(): void
    {
        $notificador = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);

        $this->actingAs($notificador)
            ->post(route('eventos.comentario.store', $evento), ['corpo' => ''])
            ->assertSessionHasErrors('corpo');
    }
}
