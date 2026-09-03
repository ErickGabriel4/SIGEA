<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taxonomia hierárquica de eventos (2 níveis), alinhada à ICPS/WHO (Quadro A.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_evento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 15)->unique();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_pai_id')->nullable()
                ->constrained('categorias_evento')->cascadeOnDelete();
            $table->string('codigo_icps', 20)->nullable()
                ->comment('Rastreabilidade à International Classification for Patient Safety (WHO, 2009)');
            $table->boolean('notificavel_anvisa')->default(false);
            $table->boolean('exige_investigacao')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_evento');
    }
};
