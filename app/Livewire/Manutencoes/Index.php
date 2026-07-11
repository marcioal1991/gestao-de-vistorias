<?php

namespace App\Livewire\Manutencoes;

use App\Models\Manutencao;
use App\Models\Vistoria;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public Vistoria $vistoria;

    public function mount(Vistoria $vistoria): void
    {
        abort_unless($vistoria->user_id === auth()->id(), 403);
        abort_unless($vistoria->manutencoesHabilitadas(), 403, 'A aba de Manutenções só é liberada após o Laudo de Entrada ser concluído.');

        $this->vistoria = $vistoria;
    }

    /**
     * Fechamento manual: a manutenção só conclui quando o usuário clica explicitamente.
     */
    public function concluir(int $manutencaoId): void
    {
        $manutencao = $this->vistoria->manutencoes()->findOrFail($manutencaoId);

        $manutencao->concluir();
    }

    public function render(): View
    {
        $manutencoes = $this->vistoria->manutencoes()->with('comodo')->latest()->get();

        return view('livewire.manutencoes.index', [
            'manutencoes' => $manutencoes,
        ]);
    }
}
