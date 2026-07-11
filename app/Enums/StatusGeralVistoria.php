<?php

namespace App\Enums;

enum StatusGeralVistoria: string
{
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';

    public function label(): string
    {
        return match ($this) {
            self::EmAndamento => 'Em Andamento',
            self::Concluida => 'Concluída',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::EmAndamento => 'bg-amber-100 text-amber-800',
            self::Concluida => 'bg-emerald-100 text-emerald-800',
        };
    }
}
