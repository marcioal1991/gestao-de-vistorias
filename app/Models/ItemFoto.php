<?php

namespace App\Models;

use App\Enums\AvaliacaoItem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['url_foto', 'descricao_avaliacao', 'avaliacao', 'parecer_ia', 'sugestao_ia', 'foto_entrada_referencia_id'])]
class ItemFoto extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'avaliacao' => AvaliacaoItem::class,
        ];
    }

    public function urlPublica(): ?string
    {
        return $this->url_foto ? Storage::disk('public')->url($this->url_foto) : null;
    }

    public function comodo(): BelongsTo
    {
        return $this->belongsTo(Comodo::class);
    }

    public function fotoEntradaReferencia(): BelongsTo
    {
        return $this->belongsTo(ItemFoto::class, 'foto_entrada_referencia_id');
    }

    public function temFotoEntradaParaComparar(): bool
    {
        return $this->foto_entrada_referencia_id !== null && $this->fotoEntradaReferencia?->url_foto !== null;
    }
}
