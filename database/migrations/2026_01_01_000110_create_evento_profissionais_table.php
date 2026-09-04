<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profissionais envolvidos ou testemunhas do evento (Quadro A.5).
 * Regra: user_id OU nome_externo deve estar preenchido (validada na aplicação).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_profissionais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_adverso_id')
                ->constrained('eventos_adversos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('nome_externo', 150)->nullable();
            $table->enum('categoria_profissional', [
                'medico', 'enfermeiro', 'tecnico_enfermagem', 'farmaceutico',
                'fisioterapeuta', 'nutricionista', 'psicologo', 'administrativo', 'outro',
            ])->default('outro');
            $table->enum('papel', ['envolvido', 'testemunha', 'notificante'])->default('envolvido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_profissionais');
    }
};
