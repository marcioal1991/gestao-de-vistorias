<?php

namespace App\Enums;

enum AssertividadeVistoria: string
{
    case Assertivo = 'assertivo';
    case NaoAssertivo = 'nao_assertivo';

    public function label(): string
    {
        return match ($this) {
            self::Assertivo => 'Assertivo',
            self::NaoAssertivo => 'Não Assertivo',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Assertivo => 'bg-emerald-100 text-emerald-800',
            self::NaoAssertivo => 'bg-red-100 text-red-800',
        };
    }
}
