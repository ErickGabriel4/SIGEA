<?php

namespace Tests\Unit;

use App\Enums\ClassificacaoIncidente;
use App\Enums\GrauDano;
use App\Models\CategoriaEvento;
use App\Models\EventoAdverso;
use App\Services\NotivisaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Elegibilidade NOTIVISA: cada condição da Seção 6.4.
 */
class NotivisaServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotivisaService $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servico = new NotivisaService();
    }

    private function evento(bool $categoriaNotificavel, GrauDano $dano, ClassificacaoIncidente $classificacao): EventoAdverso
    {
        $raiz = CategoriaEvento::factory()->create();
        $categoria = CategoriaEvento::factory()->create([
            'categoria_pai_id' => $raiz->id,
            'notificavel_anvisa' => $categoriaNotificavel,
        ]);

        $evento = new EventoAdverso();
        $evento->setRelation('categoria', $categoria);
        $evento->categoria_evento_id = $categoria->id;
        $evento->grau_dano = $dano;
        $evento->classificacao = $classificacao;

        return $evento;
    }

    public function test_categoria_notificavel_torna_elegivel(): void
    {
        $evento = $this->evento(true, GrauDano::Nenhum, ClassificacaoIncidente::NearMiss);
        $this->assertTrue($this->servico->elegivel($evento));
    }

    public function test_dano_grave_torna_elegivel(): void
    {
        $evento = $this->evento(false, GrauDano::Grave, ClassificacaoIncidente::EventoAdverso);
        $this->assertTrue($this->servico->elegivel($evento));
    }

    public function test_obito_torna_elegivel(): void
    {
        $evento = $this->evento(false, GrauDano::Obito, ClassificacaoIncidente::EventoAdverso);
        $this->assertTrue($this->servico->elegivel($evento));
    }

    public function test_never_event_torna_elegivel(): void
    {
        $evento = $this->evento(false, GrauDano::Nenhum, ClassificacaoIncidente::NeverEvent);
        $this->assertTrue($this->servico->elegivel($evento));
    }

    public function test_evento_comum_sem_dano_nao_e_elegivel(): void
    {
        $evento = $this->evento(false, GrauDano::Leve, ClassificacaoIncidente::EventoAdverso);
        $this->assertFalse($this->servico->elegivel($evento));
    }

    public function test_aplicar_marca_flag_apenas_quando_elegivel(): void
    {
        $elegivel = $this->evento(false, GrauDano::Grave, ClassificacaoIncidente::EventoAdverso);
        $comum = $this->evento(false, GrauDano::Leve, ClassificacaoIncidente::EventoAdverso);

        $this->servico->aplicar($elegivel);
        $this->servico->aplicar($comum);

        $this->assertTrue($elegivel->notificavel_anvisa);
        $this->assertFalse((bool) $comum->notificavel_anvisa);
    }
}
