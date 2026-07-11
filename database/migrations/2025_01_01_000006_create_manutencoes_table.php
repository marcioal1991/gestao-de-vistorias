<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manutencoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vistoria_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comodo_id')->constrained()->cascadeOnDelete();
            $table->string('url_foto')->nullable();
            $table->text('descricao_defeito')->nullable();
            $table->decimal('valor_custo', 10, 2)->nullable();
            $table->string('status')->default('em_aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manutencoes');
    }
};
