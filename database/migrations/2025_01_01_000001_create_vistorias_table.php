<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vistorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('codigo_imovel');
            $table->string('endereco');
            $table->string('tipo_imovel');
            $table->string('locatario');
            $table->string('status_geral')->default('em_andamento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vistorias');
    }
};
