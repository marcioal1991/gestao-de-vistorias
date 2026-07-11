<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between h-14 px-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold text-gray-800">
            <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
        </a>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-600 active:bg-gray-200">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                </div>
                <x-dropdown-link :href="route('profile')" wire:navigate>
                    {{ __('Perfil') }}
                </x-dropdown-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-dropdown-link>
                        {{ __('Sair') }}
                    </x-dropdown-link>
                </button>
            </x-slot>
        </x-dropdown>
    </div>
</nav>
