<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vistoria_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('status')->default('pendente');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamps();

            $table->unique(['vistoria_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laudos');
    }
};
