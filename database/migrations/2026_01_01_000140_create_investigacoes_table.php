<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Investigações de causa raiz — relação 1:1 com o evento (Quadro A.8).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_adverso_id')->unique()
                ->constrained('eventos_adversos')->cascadeOnDelete();
            $table->foreignId('responsavel_id')->constrained('users');
            $table->enum('metodo', [
                'cinco_porques', 'ishikawa', 'protocolo_londres', 'analise_simplificada',
            ]);
            $table->date('data_inicio');
            $table->date('data_prevista')->nullable();
            $table->date('data_conclusao')->nullable();
            $table->text('descricao_analise')->nullable();
            $table->text('causa_raiz')->nullable();
            $table->text('conclusao')->nullable();
            $table->enum('status', ['aberta', 'em_andamento', 'concluida', 'cancelada'])
                ->default('aberta');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigacoes');
    }
};
