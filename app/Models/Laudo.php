<?php

namespace App\Models;

use App\Enums\AvaliacaoItem;
use App\Enums\StatusLaudo;
use App\Enums\TipoLaudo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tipo', 'usuario_id', 'status', 'iniciado_em', 'concluido_em'])]
class Laudo extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoLaudo::class,
            'status' => StatusLaudo::class,
            'iniciado_em' => 'datetime',
            'concluido_em' => 'datetime',
        ];
    }

    public function vistoria(): BelongsTo
    {
        return $this->belongsTo(Vistoria::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function comodos(): HasMany
    {
        return $this->hasMany(Comodo::class);
    }

    /**
     * O Laudo de Saída só pode ser iniciado se o de Entrada estiver concluído e
     * não houver manutenções "Em Aberto" pendentes na vistoria.
     */
    public function podeSerIniciado(): bool
    {
        if ($this->tipo === TipoLaudo::Entrada) {
            return true;
        }

        if ($this->vistoria->laudoEntrada?->status !== StatusLaudo::Concluido) {
            return false;
        }

        return ! $this->vistoria->temManutencoesPendentes();
    }

    public function foiIniciado(): bool
    {
        return $this->iniciado_em !== null;
    }

    /**
     * Shallow copy: clona cômodos e descrições do Laudo de Entrada para o de Saída,
     * sem copiar as fotos, mantendo a referência da foto de entrada para comparação (RF04).
     */
    public function iniciarComShallowCopyDaEntrada(): void
    {
        if ($this->tipo !== TipoLaudo::Saida || $this->foiIniciado()) {
            return;
        }

        $laudoEntrada = $this->vistoria->laudoEntrada()->with('comodos.itemFotos')->first();

        $this->getConnection()->transaction(function () use ($laudoEntrada) {
            foreach ($laudoEntrada?->comodos ?? [] as $comodoEntrada) {
                $novoComodo = $this->comodos()->create([
                    'nome' => $comodoEntrada->nome,
                    'descricao' => $comodoEntrada->descricao,
                ]);

                foreach ($comodoEntrada->itemFotos as $itemEntrada) {
                    $novoComodo->itemFotos()->create([
                        'descricao_avaliacao' => $itemEntrada->descricao_avaliacao,
                        'avaliacao' => AvaliacaoItem::Pendente,
                        'foto_entrada_referencia_id' => $itemEntrada->id,
                    ]);
                }
            }

            $this->update(['iniciado_em' => now()]);
        });
    }

    /**
     * Um laudo só pode ser concluído se todos os itens estiverem "Aptas" (RF04).
     */
    public function podeSerConcluido(): bool
    {
        $itens = ItemFoto::whereIn('comodo_id', $this->comodos()->pluck('id'));

        if ($itens->doesntExist()) {
            return false;
        }

        return $itens->where('avaliacao', '!=', AvaliacaoItem::Apta)->doesntExist();
    }

    public function concluir(): bool
    {
        if (! $this->podeSerConcluido()) {
            return false;
        }

        $this->update([
            'status' => StatusLaudo::Concluido,
            'concluido_em' => now(),
        ]);

        $this->vistoria->atualizarStatusGeral();

        return true;
    }
}
