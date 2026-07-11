<?php

namespace App\Enums;

enum StatusLaudo: string
{
    case Pendente = 'pendente';
    case Concluido = 'concluido';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Concluido => 'Concluído',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendente => 'bg-amber-100 text-amber-800',
            self::Concluido => 'bg-emerald-100 text-emerald-800',
        };
    }
}
