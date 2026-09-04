<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela central do sistema — eventos adversos (Quadro A.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_adversos', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 20)->unique()
                ->comment('Formato EA-AAAA-NNNNNN, gerado pelo ProtocoloService');
            $table->foreignId('notificante_id')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('NULL em notificações anônimas');
            $table->boolean('anonima')->default(false);
            $table->foreignId('categoria_evento_id')
                ->constrained('categorias_evento')->restrictOnDelete();
            $table->foreignId('setor_ocorrencia_id')
                ->constrained('setores')->restrictOnDelete();
            $table->foreignId('setor_notificante_id')->nullable()
                ->constrained('setores')->nullOnDelete();
            $table->date('data_ocorrencia');
            $table->time('hora_ocorrencia')->nullable();
            $table->enum('turno', ['manha', 'tarde', 'noite', 'nao_informado'])
                ->default('nao_informado');
            $table->string('local_especifico', 150)->nullable();
            $table->text('descricao');
            $table->text('fatores_contribuintes')->nullable();
            $table->text('acao_imediata')->nullable();
            $table->boolean('paciente_envolvido')->default(true);
            $table->string('paciente_codigo', 64)->nullable()
                ->comment('Pseudonimizado — hash SHA-256 truncado (LGPD)');
            $table->enum('paciente_faixa_etaria', [
                'neonato', 'lactente', 'infantil', 'adolescente', 'adulto', 'idoso',
            ])->nullable();
            $table->enum('paciente_sexo', ['feminino', 'masculino', 'nao_informado'])->nullable();
            $table->enum('classificacao', [
                'circunstancia_notificavel', 'near_miss', 'incidente_sem_dano',
                'evento_adverso', 'never_event',
            ])->default('evento_adverso');
            $table->enum('grau_dano', ['nenhum', 'leve', 'moderado', 'grave', 'obito'])
                ->default('nenhum')->index();
            $table->enum('evitabilidade', [
                'evitavel', 'provavelmente_evitavel', 'provavelmente_inevitavel',
                'inevitavel', 'nao_avaliado',
            ])->default('nao_avaliado');
            $table->unsignedTinyInteger('probabilidade')->nullable()->comment('1..5');
            $table->unsignedTinyInteger('severidade')->nullable()->comment('1..5');
            $table->unsignedTinyInteger('score_risco')->nullable()
                ->storedAs('`probabilidade` * `severidade`')
                ->comment('Coluna gerada: probabilidade × severidade');
            $table->enum('nivel_risco', ['baixo', 'moderado', 'alto', 'extremo'])->nullable();
            $table->enum('status', [
                'rascunho', 'notificado', 'em_triagem', 'em_investigacao',
                'aguardando_acao', 'concluido', 'cancelado',
            ])->default('rascunho')->index();
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'critica'])->default('media');
            $table->foreignId('gestor_responsavel_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->date('prazo_conclusao')->nullable();
            $table->text('feedback_notificante')->nullable();
            $table->boolean('notificavel_anvisa')->default(false);
            $table->date('notificado_anvisa_em')->nullable();
            $table->string('protocolo_notivisa', 50)->nullable();
            $table->timestamp('triado_em')->nullable();
            $table->timestamp('investigado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('data_ocorrencia');
            $table->index(['status', 'prazo_conclusao'], 'idx_eventos_fila_pendencias');
            $table->index(['data_ocorrencia', 'setor_ocorrencia_id'], 'idx_eventos_dashboard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_adversos');
    }
};
