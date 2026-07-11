<?php

namespace App\Enums;

enum StatusManutencao: string
{
    case EmAberto = 'em_aberto';
    case Concluido = 'concluido';

    public function label(): string
    {
        return match ($this) {
            self::EmAberto => 'Em Aberto',
            self::Concluido => 'Concluído',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EmAberto => 'bg-amber-100 text-amber-800',
            self::Concluido => 'bg-emerald-100 text-emerald-800',
        };
    }
}
