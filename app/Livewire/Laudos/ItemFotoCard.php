<?php

namespace App\Livewire\Laudos;

use App\Enums\AvaliacaoItem;
use App\Enums\TipoLaudo;
use App\Models\ItemFoto;
use App\Services\OpenAIVistoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ItemFotoCard extends Component
{
    use WithFileUploads;

    public ItemFoto $itemFoto;

    public string $descricao = '';

    public $novaFoto = null;

    public bool $analisandoIa = false;

    public bool $laudoConcluido = false;

    public function mount(ItemFoto $itemFoto, bool $laudoConcluido = false): void
    {
        $this->itemFoto = $itemFoto;
        $this->descricao = $itemFoto->descricao_avaliacao ?? '';
        $this->laudoConcluido = $laudoConcluido;
    }

    public function updatedDescricao(string $valor): void
    {
        $this->itemFoto->update(['descricao_avaliacao' => $valor]);
    }

    public function updatedNovaFoto(OpenAIVistoriaService $ia): void
    {
        $inicio = microtime(true);
        Log::info('ItemFotoCard: novo upload de foto recebido', ['item_foto_id' => $this->itemFoto->id]);

        $this->validate([
            'novaFoto' => ['image', 'max:102400'],
        ]);

        $comodo = $this->itemFoto->comodo;
        $ehSaida = $comodo->laudo->tipo === TipoLaudo::Saida;

        $caminho = $this->novaFoto->store("vistorias/{$comodo->laudo_id}/{$comodo->id}", 'public');
        Log::info('ItemFotoCard: foto salva no disco', ['item_foto_id' => $this->itemFoto->id, 'caminho' => $caminho]);

        $this->itemFoto->update([
            'url_foto' => $caminho,
            'descricao_avaliacao' => $this->descricao,
        ]);

        $this->novaFoto = null;
        $this->analisandoIa = true;

        try {
            if ($ehSaida) {
                $this->analisarComparacaoComIa($ia);
            } else {
                $this->analisarDescricaoComIa($ia);
            }
        } catch (\Throwable $e) {
            Log::error('ItemFotoCard: exceção inesperada durante análise de IA', [
                'item_foto_id' => $this->itemFoto->id,
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->analisandoIa = false;
        }

        $this->itemFoto->refresh();
        $this->dispatch('item-atualizado');

        Log::info('ItemFotoCard: processamento do upload concluído', [
            'item_foto_id' => $this->itemFoto->id,
            'duracao_total_s' => round(microtime(true) - $inicio, 2),
        ]);
    }

    private function analisarDescricaoComIa(OpenAIVistoriaService $ia): void
    {
        $descricao = $ia->descreverFoto($this->itemFoto->url_foto);

        if ($descricao) {
            $this->descricao = $descricao;
            $this->itemFoto->update(['descricao_avaliacao' => $descricao]);
        }
    }

    private function analisarComparacaoComIa(OpenAIVistoriaService $ia): void
    {
        $itemEntrada = $this->itemFoto->fotoEntradaReferencia;

        if (! $itemEntrada || ! $itemEntrada->url_foto) {
            return;
        }

        $resultado = $ia->compararFotos($itemEntrada->url_foto, $this->itemFoto->url_foto);

        if ($resultado) {
            $this->itemFoto->update([
                'parecer_ia' => $resultado['analise'],
                'sugestao_ia' => $resultado['sugestao']->value,
            ]);
        }
    }

    /**
     * Não é possível avaliar (Apta/Não Apta) um item sem antes enviar a foto —
     * evita concluir o laudo com itens "aprovados" que nunca foram fotografados.
     */
    public function marcarAvaliacao(string $valor): void
    {
        if (! $this->itemFoto->url_foto) {
            Log::warning('ItemFotoCard: tentativa de avaliar item sem foto bloqueada', ['item_foto_id' => $this->itemFoto->id]);

            return;
        }

        $this->itemFoto->update(['avaliacao' => AvaliacaoItem::from($valor)]);
        $this->itemFoto->refresh();
        $this->dispatch('item-atualizado');
    }

    /**
     * Remove apenas a foto (e a análise da IA associada a ela), mantendo a descrição
     * e o item para o usuário capturar uma nova foto.
     */
    public function removerFoto(): void
    {
        if ($this->itemFoto->url_foto) {
            Storage::disk('public')->delete($this->itemFoto->url_foto);
        }

        $this->itemFoto->update([
            'url_foto' => null,
            'avaliacao' => AvaliacaoItem::Pendente,
            'parecer_ia' => null,
            'sugestao_ia' => null,
        ]);

        Log::info('ItemFotoCard: foto removida', ['item_foto_id' => $this->itemFoto->id]);

        $this->itemFoto->refresh();
        $this->dispatch('item-atualizado');
    }

    /**
     * Remove o item de avaliação inteiro (descrição + foto), para desfazer um
     * item adicionado por engano.
     */
    public function removerItem(): void
    {
        if ($this->itemFoto->url_foto) {
            Storage::disk('public')->delete($this->itemFoto->url_foto);
        }

        Log::info('ItemFotoCard: item removido', ['item_foto_id' => $this->itemFoto->id]);

        $this->itemFoto->delete();

        $this->dispatch('item-atualizado');
    }

    public function render(): View
    {
        return view('livewire.laudos.item-foto-card');
    }
}
