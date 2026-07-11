<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comodo_id')->constrained()->cascadeOnDelete();
            $table->string('url_foto')->nullable();
            $table->text('descricao_avaliacao')->nullable();
            $table->string('avaliacao')->default('pendente');
            $table->text('parecer_ia')->nullable();
            $table->string('sugestao_ia')->nullable();
            $table->foreignId('foto_entrada_referencia_id')
                ->nullable()
                ->constrained('item_fotos')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_fotos');
    }
};
