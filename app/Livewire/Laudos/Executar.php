<?php

namespace App\Livewire\Laudos;

use App\Enums\AvaliacaoItem;
use App\Enums\StatusLaudo;
use App\Enums\TipoLaudo;
use App\Models\Laudo;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Executar extends Component
{
    public Laudo $laudo;

    public string $novoComodoNome = '';

    public string $novoComodoDescricao = '';

    public bool $mostrarFormComodo = false;

    public ?string $erroConclusao = null;

    public function mount(Laudo $laudo): void
    {
        abort_unless($laudo->vistoria->user_id === auth()->id(), 403);

        if ($laudo->tipo === TipoLaudo::Saida && $laudo->vistoria->laudoEntrada?->status !== StatusLaudo::Concluido) {
            abort(403, 'O Laudo de Entrada precisa estar concluído antes de iniciar o de Saída.');
        }

        if ($laudo->tipo === TipoLaudo::Saida && $laudo->vistoria->temManutencoesPendentes()) {
            abort(403, 'Existem manutenções pendentes. Conclua-as para liberar o laudo de saída.');
        }

        if ($laudo->tipo === TipoLaudo::Saida && ! $laudo->foiIniciado()) {
            $laudo->iniciarComShallowCopyDaEntrada();
        }

        $this->laudo = $laudo;
    }

    #[On('item-atualizado')]
    public function refresh(): void
    {
        // Força o Livewire a re-renderizar com os dados atualizados do banco.
    }

    public function adicionarComodo(): void
    {
        $dados = $this->validate([
            'novoComodoNome' => ['required', 'string', 'max:100'],
            'novoComodoDescricao' => ['nullable', 'string', 'max:500'],
        ]);

        $this->laudo->comodos()->create([
            'nome' => $dados['novoComodoNome'],
            'descricao' => $dados['novoComodoDescricao'],
        ]);

        $this->reset(['novoComodoNome', 'novoComodoDescricao', 'mostrarFormComodo']);
    }

    public function adicionarItem(int $comodoId): void
    {
        $comodo = $this->laudo->comodos()->findOrFail($comodoId);

        $comodo->itemFotos()->create();
    }

    public function concluirLaudo(): void
    {
        $this->erroConclusao = null;

        if (! $this->laudo->concluir()) {
            $this->erroConclusao = 'Não é possível concluir: cadastre ao menos um item e marque todas as fotos como "Apta".';

            return;
        }

        session()->flash('sucesso', 'Laudo de '.$this->laudo->tipo->label().' concluído com sucesso!');

        $this->redirect(route('vistorias.show', $this->laudo->vistoria), navigate: true);
    }

    public function render(): View
    {
        $this->laudo->load('comodos.itemFotos.fotoEntradaReferencia');

        $itens = $this->laudo->comodos->flatMap->itemFotos;
        $totalItens = $itens->count();
        $itensAptos = $itens->where('avaliacao', AvaliacaoItem::Apta)->count();

        return view('livewire.laudos.executar', [
            'ehSaida' => $this->laudo->tipo === TipoLaudo::Saida,
            'podeSerConcluido' => $this->laudo->podeSerConcluido(),
            'totalItens' => $totalItens,
            'itensAptos' => $itensAptos,
            'itensPendentes' => $totalItens - $itensAptos,
        ]);
    }
}
