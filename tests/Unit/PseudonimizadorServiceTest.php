<?php

namespace Tests\Unit;

use App\Services\PseudonimizadorService;
use Tests\TestCase;

/**
 * Pseudonimização do paciente (Seção 5.4 / LGPD).
 */
class PseudonimizadorServiceTest extends TestCase
{
    private PseudonimizadorService $servico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servico = new PseudonimizadorService();
    }

    public function test_codigo_tem_prefixo_e_16_caracteres_de_hash(): void
    {
        $codigo = $this->servico->codificar('PRONTUARIO-123');

        $this->assertMatchesRegularExpression('/^PAC-[0-9a-f]{16}$/', $codigo);
    }

    public function test_mesmo_identificador_gera_o_mesmo_codigo(): void
    {
        $this->assertSame(
            $this->servico->codificar('PRONT-42'),
            $this->servico->codificar('PRONT-42')
        );
    }

    public function test_identificadores_diferentes_geram_codigos_diferentes(): void
    {
        $this->assertNotSame(
            $this->servico->codificar('PRONT-1'),
            $this->servico->codificar('PRONT-2')
        );
    }

    public function test_identificador_original_nao_aparece_no_codigo(): void
    {
        $codigo = $this->servico->codificar('JOAO-DA-SILVA-123456');

        $this->assertStringNotContainsString('JOAO', strtoupper($codigo));
        $this->assertStringNotContainsString('123456', $codigo);
    }

    public function test_valor_vazio_retorna_nulo(): void
    {
        $this->assertNull($this->servico->codificar(null));
        $this->assertNull($this->servico->codificar(''));
        $this->assertNull($this->servico->codificar('   '));
    }

    public function test_codigo_ja_pseudonimizado_nao_e_recodificado(): void
    {
        $codigo = $this->servico->codificar('PRONT-42');

        $this->assertSame($codigo, $this->servico->codificar($codigo));
    }
}
