<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'descricao'])]
class Comodo extends Model
{
    use HasFactory;

    public function laudo(): BelongsTo
    {
        return $this->belongsTo(Laudo::class);
    }

    public function itemFotos(): HasMany
    {
        return $this->hasMany(ItemFoto::class);
    }
}
