<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de usuários do SIGEA (Quadro A.1) + tabelas de sessão e recuperação de senha.
 * A FK de setor_id é adicionada na migration de setores (dependência circular users ↔ setores).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('matricula', 30)->nullable()->unique();
            $table->string('registro_conselho', 30)->nullable();
            $table->enum('categoria_profissional', [
                'medico', 'enfermeiro', 'tecnico_enfermagem', 'farmaceutico',
                'fisioterapeuta', 'nutricionista', 'psicologo', 'administrativo', 'outro',
            ])->default('outro');
            $table->string('cargo', 100)->nullable();
            $table->unsignedBigInteger('setor_id')->nullable()->index();
            $table->string('telefone', 20)->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamp('ultimo_acesso_em')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
