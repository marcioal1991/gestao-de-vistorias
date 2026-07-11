<?php

namespace App\Models;

use App\Enums\StatusManutencao;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['vistoria_id', 'comodo_id', 'url_foto', 'descricao_defeito', 'valor_custo', 'status'])]
class Manutencao extends Model
{
    use HasFactory;

    protected $table = 'manutencoes';

    protected function casts(): array
    {
        return [
            'status' => StatusManutencao::class,
            'valor_custo' => 'decimal:2',
        ];
    }

    public function vistoria(): BelongsTo
    {
        return $this->belongsTo(Vistoria::class);
    }

    public function comodo(): BelongsTo
    {
        return $this->belongsTo(Comodo::class);
    }

    public function urlPublica(): ?string
    {
        return $this->url_foto ? Storage::disk('public')->url($this->url_foto) : null;
    }

    /**
     * Uma manutenção nunca conclui sozinha — exige clique explícito do usuário.
     */
    public function concluir(): void
    {
        $this->update(['status' => StatusManutencao::Concluido]);
    }
}
