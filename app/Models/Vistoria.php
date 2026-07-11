<?php

namespace App\Models;

use App\Enums\AssertividadeVistoria;
use App\Enums\StatusGeralVistoria;
use App\Enums\StatusLaudo;
use App\Enums\StatusManutencao;
use App\Enums\TipoLaudo;
use App\Services\OpenAIVistoriaService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

#[Fillable([
    'user_id', 'codigo_imovel', 'endereco', 'tipo_imovel', 'locatario', 'status_geral',
    'parecer_ia_final', 'assertivo_ia', 'analisado_em',
])]
class Vistoria extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status_geral' => StatusGeralVistoria::class,
            'assertivo_ia' => AssertividadeVistoria::class,
            'analisado_em' => 'datetime',
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
     * Ao fechar, dispara a análise final da IA sobre toda a linha do tempo.
     */
    public function atualizarStatusGeral(): void
    {
        $jaEstavaConcluida = $this->status_geral === StatusGeralVistoria::Concluida;

        $ambosConcluidos = $this->laudos()
            ->where('status', '!=', StatusLaudo::Concluido)
            ->doesntExist();

        $novoStatus = $ambosConcluidos ? StatusGeralVistoria::Concluida : StatusGeralVistoria::EmAndamento;

        if ($this->status_geral !== $novoStatus) {
            $this->update(['status_geral' => $novoStatus]);
        }

        if ($novoStatus === StatusGeralVistoria::Concluida && ! $jaEstavaConcluida) {
            $this->gerarAnaliseFinalComIa();
        }
    }

    /**
     * Lê, em ordem cronológica, as descrições de todas as fotos da vistoria
     * (Laudo de Entrada, Manutenções e Laudo de Saída) e pede à IA um parecer
     * sobre se o laudo foi assertivo. É best-effort: se a IA falhar, a vistoria
     * continua concluída normalmente, só o parecer fica em branco.
     */
    public function gerarAnaliseFinalComIa(): void
    {
        try {
            $linhaDoTempo = $this->construirLinhaDoTempoParaIa();

            $resultado = app(OpenAIVistoriaService::class)->avaliarVistoriaCompleta($linhaDoTempo);

            if ($resultado) {
                $this->update([
                    'parecer_ia_final' => $resultado['analise'],
                    'assertivo_ia' => $resultado['assertivo'],
                    'analisado_em' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Vistoria: exceção ao gerar análise final da IA', [
                'vistoria_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function construirLinhaDoTempoParaIa(): string
    {
        $this->loadMissing(['laudos.comodos.itemFotos', 'manutencoes.comodo']);

        $eventos = collect();

        foreach ($this->laudos as $laudo) {
            foreach ($laudo->comodos as $comodo) {
                foreach ($comodo->itemFotos as $item) {
                    $eventos->push([
                        'data' => $item->created_at,
                        'texto' => sprintf(
                            'Laudo de %s — Cômodo "%s": %s (Avaliação: %s)%s',
                            $laudo->tipo->label(),
                            $comodo->nome,
                            $item->descricao_avaliacao ?: '(sem descrição preenchida)',
                            $item->avaliacao->label(),
                            $item->parecer_ia ? ' [Parecer da IA na comparação: '.$item->parecer_ia.']' : ''
                        ),
                    ]);
                }
            }
        }

        foreach ($this->manutencoes as $manutencao) {
            $eventos->push([
                'data' => $manutencao->created_at,
                'texto' => sprintf(
                    'Manutenção — Cômodo "%s": %s (Custo: R$ %s, Status: %s)',
                    $manutencao->comodo->nome,
                    $manutencao->descricao_defeito ?: '(sem descrição preenchida)',
                    number_format((float) $manutencao->valor_custo, 2, ',', '.'),
                    $manutencao->status->label()
                ),
            ]);
        }

        return $eventos->sortBy('data')->values()
            ->map(fn ($evento, $indice) => ($indice + 1).'. ['.$evento['data']->format('d/m/Y H:i').'] '.$evento['texto'])
            ->implode("\n");
    }
}
