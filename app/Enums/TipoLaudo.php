<?php

namespace App\Enums;

enum TipoLaudo: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }
}
