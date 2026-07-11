<?php

namespace App\Livewire\Vistorias;

use App\Models\Vistoria;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Detalhes extends Component
{
    public Vistoria $vistoria;

    public function mount(Vistoria $vistoria): void
    {
        abort_unless($vistoria->user_id === auth()->id(), 403);

        $this->vistoria = $vistoria;
    }

    public function render(): View
    {
        $this->vistoria->load(['laudoEntrada', 'laudoSaida']);

        return view('livewire.vistorias.detalhes');
    }
}
