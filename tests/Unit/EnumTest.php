<?php

namespace Tests\Unit;

use App\Enums\CategoriaCausa;
use App\Enums\CategoriaProfissional;
use App\Enums\ClassificacaoIncidente;
use App\Enums\Eficacia;
use App\Enums\Evitabilidade;
use App\Enums\FaixaEtaria;
use App\Enums\GrauDano;
use App\Enums\MetodoInvestigacao;
use App\Enums\NivelRisco;
use App\Enums\PapelProfissional;
use App\Enums\Prioridade;
use App\Enums\SexoPaciente;
use App\Enums\StatusAcao;
use App\Enums\StatusEvento;
use App\Enums\StatusInvestigacao;
use App\Enums\TipoAcao;
use App\Enums\TipoSetor;
use App\Enums\Turno;
use PHPUnit\Framework\TestCase;

/**
 * Enums de domínio: label() presente em todos os casos, cor() nos visuais.
 */
class EnumTest extends TestCase
{
    /** Enums que expõem label(). */
    private const COM_LABEL = [
        StatusEvento::class, GrauDano::class, ClassificacaoIncidente::class,
        Evitabilidade::class, NivelRisco::class, Prioridade::class,
        Turno::class, FaixaEtaria::class, SexoPaciente::class,
        CategoriaProfissional::class, TipoSetor::class, PapelProfissional::class,
        MetodoInvestigacao::class, StatusInvestigacao::class, CategoriaCausa::class,
        TipoAcao::class, StatusAcao::class, Eficacia::class,
    ];

    /** Enums que expõem cor() (badges). */
    private const COM_COR = [
        StatusEvento::class, GrauDano::class, ClassificacaoIncidente::class,
        Evitabilidade::class, NivelRisco::class, Prioridade::class,
        StatusInvestigacao::class, StatusAcao::class, Eficacia::class,
    ];

    public function test_todos_os_casos_tem_label_nao_vazio(): void
    {
        foreach (self::COM_LABEL as $enum) {
            foreach ($enum::cases() as $caso) {
                $this->assertNotSame('', $caso->label(), "{$enum}::{$caso->name} sem label");
            }
        }
    }

    public function test_enums_visuais_tem_cor_nao_vazia(): void
    {
        foreach (self::COM_COR as $enum) {
            foreach ($enum::cases() as $caso) {
                $this->assertNotSame('', $caso->cor(), "{$enum}::{$caso->name} sem cor");
            }
        }
    }

    public function test_regras_auxiliares_dos_enums(): void
    {
        $this->assertTrue(GrauDano::Grave->ehGraveOuObito());
        $this->assertTrue(GrauDano::Obito->ehGraveOuObito());
        $this->assertFalse(GrauDano::Leve->ehGraveOuObito());

        $this->assertTrue(Evitabilidade::Evitavel->contaComoEvitavel());
        $this->assertTrue(Evitabilidade::ProvavelmenteEvitavel->contaComoEvitavel());
        $this->assertFalse(Evitabilidade::Inevitavel->contaComoEvitavel());

        $this->assertTrue(StatusAcao::Concluida->tratada());
        $this->assertTrue(StatusAcao::Cancelada->tratada());
        $this->assertFalse(StatusAcao::Pendente->tratada());
    }
}
