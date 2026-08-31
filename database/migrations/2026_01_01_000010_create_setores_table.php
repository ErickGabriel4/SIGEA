<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setores hospitalares (Quadro A.2). Também adiciona a FK users.setor_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('sigla', 15)->unique();
            $table->enum('tipo', [
                'clinico', 'cirurgico', 'uti', 'emergencia',
                'ambulatorial', 'diagnostico', 'apoio', 'administrativo',
            ]);
            $table->foreignId('responsavel_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('leitos')->nullable()
                ->comment('Denominador do indicador de eventos por 1.000 pacientes-dia');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // FK pendente da dependência circular users ↔ setores
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('setor_id')->references('id')->on('setores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['setor_id']);
        });
        Schema::dropIfExists('setores');
    }
};
