<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha imutável do workflow do evento (Quadro A.7) — sem update, sem delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_adverso_id')
                ->constrained('eventos_adversos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('status_anterior', 30)->nullable();
            $table->string('status_novo', 30);
            $table->string('acao', 60);
            $table->text('observacao')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_historico');
    }
};
