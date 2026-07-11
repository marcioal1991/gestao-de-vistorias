<div class="px-4 pt-4">
    <h1 class="text-xl font-bold text-gray-900 mb-4">Minhas Vistorias</h1>

    {{-- Indicadores (RF02) --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $totalVistorias }}</p>
            <p class="text-[11px] text-gray-500 leading-tight mt-1">Total</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $emAndamento }}</p>
            <p class="text-[11px] text-gray-500 leading-tight mt-1">Em Andamento</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $concluidas }}</p>
            <p class="text-[11px] text-gray-500 leading-tight mt-1">Concluídas</p>
        </div>
    </div>

    {{-- Listagem --}}
    <div class="space-y-3">
        @forelse ($vistorias as $vistoria)
            <a href="{{ route('vistorias.show', $vistoria) }}" wire:navigate
               class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 active:bg-gray-50">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $vistoria->codigo_imovel }}</p>
                        <p class="text-sm text-gray-600 truncate">{{ $vistoria->endereco }}</p>
                        <p class="text-xs text-gray-500 mt-1">Locatário: {{ $vistoria->locatario }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium px-2 py-1 rounded-full {{ $vistoria->status_geral->badgeClass() }}">
                        {{ $vistoria->status_geral->label() }}
                    </span>
                </div>
            </a>
        @empty
            <div class="text-center py-16">
                <p class="text-gray-500">Nenhuma vistoria cadastrada ainda.</p>
                <p class="text-sm text-gray-400 mt-1">Toque no botão "+" para começar.</p>
            </div>
        @endforelse
    </div>

    {{-- Botão flutuante "Nova Vistoria" (RF02) --}}
    <div class="fixed inset-x-0 bottom-6 max-w-md mx-auto px-6 flex justify-end pointer-events-none">
        <a href="{{ route('vistorias.criar') }}" wire:navigate
           class="pointer-events-auto h-14 w-14 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center active:bg-indigo-700"
           aria-label="Nova Vistoria">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </a>
    </div>
</div>
