<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Causas identificadas na investigação — eixos de Ishikawa / cadeia dos 5 Porquês (Quadro A.9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigacao_causas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigacao_id')
                ->constrained('investigacoes')->cascadeOnDelete();
            $table->enum('categoria', [
                'pessoa', 'processo', 'equipamento', 'ambiente', 'material', 'gestao',
            ]);
            $table->text('descricao');
            $table->unsignedTinyInteger('ordem')->default(1)
                ->comment('Sequência usada no método dos 5 Porquês');
            $table->boolean('eh_causa_raiz')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigacao_causas');
    }
};
