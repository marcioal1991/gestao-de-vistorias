<?php

namespace App\Models;

use App\Enums\StatusGeralVistoria;
use App\Enums\StatusLaudo;
use App\Enums\StatusManutencao;
use App\Enums\TipoLaudo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'codigo_imovel', 'endereco', 'tipo_imovel', 'locatario', 'status_geral'])]
class Vistoria extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status_geral' => StatusGeralVistoria::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function laudos(): HasMany
    {
        return $this->hasMany(Laudo::class);
    }

    public function laudoEntrada(): HasOne
    {
        return $this->hasOne(Laudo::class)->where('tipo', TipoLaudo::Entrada);
    }

    public function laudoSaida(): HasOne
    {
        return $this->hasOne(Laudo::class)->where('tipo', TipoLaudo::Saida);
    }

    public function manutencoes(): HasMany
    {
        return $this->hasMany(Manutencao::class);
    }

    /**
     * A aba de Manutenções só fica habilitada para edição após o Laudo de
     * Entrada ser concluído.
     */
    public function manutencoesHabilitadas(): bool
    {
        return $this->laudoEntrada?->status === StatusLaudo::Concluido;
    }

    public function manutencoesPendentesCount(): int
    {
        return $this->manutencoes()->where('status', StatusManutencao::EmAberto)->count();
    }

    public function temManutencoesPendentes(): bool
    {
        return $this->manutencoes()->where('status', StatusManutencao::EmAberto)->exists();
    }

    /**
     * Cria a vistoria e já vincula os dois laudos vazios (Entrada e Saída), conforme RF03.
     */
    public static function criarComLaudos(array $dados, User $user): self
    {
        return static::query()->getConnection()->transaction(function () use ($dados, $user) {
            $vistoria = static::create([
                ...$dados,
                'user_id' => $user->id,
                'status_geral' => StatusGeralVistoria::EmAndamento,
            ]);

            foreach ([TipoLaudo::Entrada, TipoLaudo::Saida] as $tipo) {
                $vistoria->laudos()->create([
                    'usuario_id' => $user->id,
                    'tipo' => $tipo,
                    'status' => StatusLaudo::Pendente,
                ]);
            }

            return $vistoria;
        });
    }

    /**
     * A vistoria só fecha quando os dois laudos estiverem concluídos (RF04).
     */
    public function atualizarStatusGeral(): void
    {
        $ambosConcluidos = $this->laudos()
            ->where('status', '!=', StatusLaudo::Concluido)
            ->doesntExist();

        $novoStatus = $ambosConcluidos ? StatusGeralVistoria::Concluida : StatusGeralVistoria::EmAndamento;

        if ($this->status_geral !== $novoStatus) {
            $this->update(['status_geral' => $novoStatus]);
        }
    }
}
