<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentários polimórficos (Quadro A.11). interno=true é oculto do notificador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->string('comentavel_type');
            $table->unsignedBigInteger('comentavel_id');
            $table->foreignId('user_id')->constrained('users');
            $table->text('corpo');
            $table->boolean('interno')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['comentavel_type', 'comentavel_id'], 'idx_comentarios_comentavel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
