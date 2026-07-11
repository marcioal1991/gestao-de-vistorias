<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Não pode haver duas vistorias "Em Andamento" para o mesmo código de imóvel.
     * Índice parcial (só cobre status_geral = em_andamento) para não impedir
     * reaberturas futuras do mesmo imóvel após uma vistoria já concluída.
     */
    public function up(): void
    {
        DB::statement(
            "CREATE UNIQUE INDEX vistorias_codigo_imovel_em_andamento_unique
             ON vistorias (codigo_imovel)
             WHERE status_geral = 'em_andamento'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS vistorias_codigo_imovel_em_andamento_unique');
    }
};
