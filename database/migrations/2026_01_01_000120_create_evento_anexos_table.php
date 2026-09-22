<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos do evento — armazenamento privado, download por rota autenticada (Quadro A.6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_adverso_id')
                ->constrained('eventos_adversos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('nome_original', 255);
            $table->string('caminho', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('tamanho')->comment('Bytes — máx. 10.485.760 (10 MB)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_anexos');
    }
};
