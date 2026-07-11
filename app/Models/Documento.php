<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['vistoria_id', 'usuario_id', 'nome_original', 'caminho_arquivo', 'tipo_mime', 'tamanho'])]
class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    public function vistoria(): BelongsTo
    {
        return $this->belongsTo(Vistoria::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function urlPublica(): string
    {
        return Storage::disk('public')->url($this->caminho_arquivo);
    }

    public function extensao(): string
    {
        return strtolower(pathinfo($this->nome_original, PATHINFO_EXTENSION));
    }

    public function tamanhoFormatado(): string
    {
        $bytes = $this->tamanho;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
