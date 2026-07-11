<?php

use App\Livewire\Dashboard;
use App\Livewire\Laudos\Executar as ExecutarLaudo;
use App\Livewire\Vistorias\Criar as CriarVistoria;
use App\Livewire\Vistorias\Detalhes as DetalhesVistoria;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('vistorias/criar', CriarVistoria::class)->name('vistorias.criar');
    Route::get('vistorias/{vistoria}', DetalhesVistoria::class)->name('vistorias.show');

    Route::get('laudos/{laudo}', ExecutarLaudo::class)->name('laudos.executar');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
