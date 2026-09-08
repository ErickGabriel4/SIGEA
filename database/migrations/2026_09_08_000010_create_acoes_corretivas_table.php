<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ações corretivas decorrentes do evento/investigação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acoes_corretivas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_adverso_id')
                ->constrained('eventos_adversos')->cascadeOnDelete();
            $table->foreignId('investigacao_id')->nullable()
                ->constrained('investigacoes')->nullOnDelete();
            $table->string('titulo', 180);
            $table->text('descricao');
            $table->enum('tipo', ['corretiva', 'preventiva', 'melhoria', 'treinamento', 'barreira']);
            $table->foreignId('responsavel_id')->constrained('users');
            $table->foreignId('setor_id')->nullable()
                ->constrained('setores')->nullOnDelete();
            $table->date('prazo');
            $table->date('data_conclusao')->nullable();
            $table->enum('status', ['pendente', 'em_andamento', 'concluida', 'atrasada', 'cancelada'])
                ->default('pendente');
            $table->enum('eficacia', ['nao_avaliada', 'eficaz', 'parcialmente_eficaz', 'ineficaz'])
                ->default('nao_avaliada');
            $table->text('evidencia_conclusao')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'prazo'], 'idx_acoes_status_prazo');
            $table->index('responsavel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acoes_corretivas');
    }
};
