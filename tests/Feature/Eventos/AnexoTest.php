<?php

namespace Tests\Feature\Eventos;

use App\Models\EventoAdverso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Anexos do evento — storage privado, download autenticado/autorizado, MIME válido.
 */
class AnexoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semearPerfis();
        Storage::fake('local');
    }

    private function eventoDoNotificador($notificador): EventoAdverso
    {
        return EventoAdverso::factory()->create(['notificante_id' => $notificador->id]);
    }

    public function test_upload_valido_grava_em_storage_privado(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($notificador);

        $this->actingAs($notificador)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $anexo = $evento->anexos()->first();
        $this->assertNotNull($anexo);
        $this->assertStringStartsWith("privado/eventos/{$evento->id}/", $anexo->caminho);
        Storage::disk('local')->assertExists($anexo->caminho);
    }

    public function test_mime_invalido_e_rejeitado(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($notificador);

        $this->actingAs($notificador)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('script.exe', 50, 'application/x-msdownload'),
        ])->assertSessionHasErrors('anexo');

        $this->assertSame(0, $evento->anexos()->count());
    }

    public function test_arquivo_acima_de_10mb_e_rejeitado(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($notificador);

        $this->actingAs($notificador)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf'),
        ])->assertSessionHasErrors('anexo');
    }

    public function test_download_exige_autenticacao(): void
    {
        $notificador = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($notificador);

        $this->actingAs($notificador)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ]);
        $anexo = $evento->anexos()->first();

        $this->post('/logout');
        $this->get(route('eventos.anexo.download', [$evento, $anexo]))->assertRedirect('/login');
    }

    public function test_download_exige_autorizacao_sobre_o_evento(): void
    {
        $dono = $this->criarNotificador();
        $intruso = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($dono);

        $this->actingAs($dono)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ]);
        $anexo = $evento->anexos()->first();

        $this->actingAs($intruso)
            ->get(route('eventos.anexo.download', [$evento, $anexo]))
            ->assertForbidden();

        $this->actingAs($dono)
            ->get(route('eventos.anexo.download', [$evento, $anexo]))
            ->assertOk();
    }

    public function test_dono_remove_o_proprio_anexo(): void
    {
        $dono = $this->criarNotificador();
        $evento = $this->eventoDoNotificador($dono);

        $this->actingAs($dono)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ]);
        $anexo = $evento->anexos()->first();

        $this->actingAs($dono)
            ->delete(route('eventos.anexo.destroy', [$evento, $anexo]))
            ->assertRedirect();

        $this->assertSame(0, $evento->anexos()->count());
        Storage::disk('local')->assertMissing($anexo->caminho);
    }

    public function test_evento_concluido_nao_aceita_novos_anexos(): void
    {
        $dono = $this->criarNotificador();
        $evento = EventoAdverso::factory()->create([
            'notificante_id' => $dono->id,
            'status' => \App\Enums\StatusEvento::Concluido,
        ]);

        $this->actingAs($dono)->post(route('eventos.anexo.store', $evento), [
            'anexo' => UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }
}
