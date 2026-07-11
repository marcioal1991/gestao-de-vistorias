<div class="px-4 pt-4 pb-28" x-data="{ zoom: null }">
    <div class="flex items-center gap-2 mb-1">
        <a href="{{ route('vistorias.show', $vistoria) }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">🛠️ Manutenções</h1>
    </div>
    <p class="text-xs text-gray-500 mb-4 ml-9">{{ $vistoria->codigo_imovel }} &middot; {{ $vistoria->endereco }}</p>

    <div class="space-y-3">
        @forelse ($manutencoes as $manutencao)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex gap-3">
                    @if ($manutencao->urlPublica())
                        <button type="button" @click="zoom = {{ $manutencao->id }}" class="shrink-0">
                            <img src="{{ $manutencao->urlPublica() }}" alt="Foto do defeito"
                                 class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                        </button>
                        <div x-show="zoom === {{ $manutencao->id }}" x-cloak @click="zoom = null"
                             class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-6">
                            <img src="{{ $manutencao->urlPublica() }}" alt="Foto do defeito (ampliada)" class="max-h-full max-w-full rounded-lg">
                        </div>
                    @else
                        <div class="h-16 w-16 shrink-0 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] text-gray-400 text-center px-1">
                            Sem foto
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-gray-900">{{ $manutencao->comodo->nome }}</p>
                            <span class="shrink-0 text-xs font-medium px-2 py-1 rounded-full {{ $manutencao->status->badgeClass() }}">
                                {{ $manutencao->status->label() }}
                            </span>
                        </div>
                        @if ($manutencao->descricao_defeito)
                            <p class="text-xs text-gray-600 mt-1">{{ $manutencao->descricao_defeito }}</p>
                        @endif
                        <p class="text-sm font-semibold text-gray-900 mt-1">
                            R$ {{ number_format((float) $manutencao->valor_custo, 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                @if ($manutencao->status === \App\Enums\StatusManutencao::EmAberto)
                    <button wire:click="concluir({{ $manutencao->id }})"
                            wire:confirm="Marcar esta manutenção como concluída?"
                            class="mt-3 w-full py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold active:bg-emerald-700">
                        Concluir Manutenção
                    </button>
                @endif
            </div>
        @empty
            <div class="text-center py-16">
                <p class="text-gray-500">Nenhuma manutenção registrada ainda.</p>
                <p class="text-sm text-gray-400 mt-1">Toque no botão "+" para adicionar.</p>
            </div>
        @endforelse
    </div>

    <div class="fixed inset-x-0 bottom-6 max-w-md mx-auto px-6 flex justify-end pointer-events-none">
        <a href="{{ route('manutencoes.criar', $vistoria) }}" wire:navigate
           class="pointer-events-auto h-14 w-14 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center active:bg-indigo-700"
           aria-label="Nova Manutenção">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </a>
    </div>
</div>
