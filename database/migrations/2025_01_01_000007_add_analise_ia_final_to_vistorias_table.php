<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vistorias', function (Blueprint $table) {
            $table->text('parecer_ia_final')->nullable();
            $table->string('assertivo_ia')->nullable();
            $table->timestamp('analisado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vistorias', function (Blueprint $table) {
            $table->dropColumn(['parecer_ia_final', 'assertivo_ia', 'analisado_em']);
        });
    }
};
