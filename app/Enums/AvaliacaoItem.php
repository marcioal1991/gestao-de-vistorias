<?php

namespace App\Enums;

enum AvaliacaoItem: string
{
    case Pendente = 'pendente';
    case Apta = 'apta';
    case NaoApta = 'nao_apta';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Apta => 'Apta',
            self::NaoApta => 'Não Apta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pendente => 'bg-slate-100 text-slate-600',
            self::Apta => 'bg-emerald-100 text-emerald-800',
            self::NaoApta => 'bg-red-100 text-red-800',
        };
    }
}
