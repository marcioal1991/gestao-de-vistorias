<?php

namespace App\Livewire;

use App\Enums\StatusGeralVistoria;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        $vistorias = auth()->user()
            ->vistorias()
            ->latest()
            ->get();

        return view('livewire.dashboard', [
            'vistorias' => $vistorias,
            'totalVistorias' => $vistorias->count(),
            'emAndamento' => $vistorias->where('status_geral', StatusGeralVistoria::EmAndamento)->count(),
            'concluidas' => $vistorias->where('status_geral', StatusGeralVistoria::Concluida)->count(),
        ]);
    }
}
