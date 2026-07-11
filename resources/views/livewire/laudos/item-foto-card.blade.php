<div class="rounded-lg border border-gray-200 p-3" x-data="{ zoomEntrada: false, zoomAtual: false }">

    @unless ($laudoConcluido)
        <div class="flex justify-end mb-1 -mt-1 -mr-1">
            <button type="button" wire:click="removerItem"
                    wire:confirm="Remover este item de avaliação? Essa ação não pode ser desfeita."
                    class="inline-flex items-center gap-1 text-xs text-red-600 px-2 py-1 rounded-md active:bg-red-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                Remover item
            </button>
        </div>
    @endunless

    {{-- Comparação: miniatura da foto de Entrada (RF05 / RF06 - Laudo de Saída) --}}
    @if ($itemFoto->fotoEntradaReferencia)
        <div class="mb-3">
            <p class="text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1">Foto da Entrada (referência)</p>
            @if ($itemFoto->fotoEntradaReferencia->urlPublica())
                <button type="button" @click="zoomEntrada = true" class="block">
                    <img src="{{ $itemFoto->fotoEntradaReferencia->urlPublica() }}" alt="Foto da entrada"
                         class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                </button>

                <div x-show="zoomEntrada" x-cloak @click="zoomEntrada = false"
                     class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-6">
                    <img src="{{ $itemFoto->fotoEntradaReferencia->urlPublica() }}" alt="Foto da entrada (ampliada)"
                         class="max-h-full max-w-full rounded-lg">
                </div>
            @else
                <div class="h-20 w-20 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-[10px] text-gray-400 text-center px-1">
                    Sem foto na entrada
                </div>
            @endif
        </div>
    @endif

    {{-- Descrição --}}
    <label class="block text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1">Descrição da avaliação</label>
    <textarea wire:model.blur="descricao" rows="2" @disabled($laudoConcluido)
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm py-2 px-3 focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
              placeholder="O que está sendo avaliado nesta foto?"></textarea>

    {{-- Captura de foto --}}
    <div class="mt-3">
        <p class="text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1">
            {{ $itemFoto->fotoEntradaReferencia ? 'Nova foto (saída)' : 'Foto' }}
        </p>

        <div class="flex items-center gap-3">
            @if ($itemFoto->urlPublica())
                <div class="relative shrink-0">
                    <button type="button" @click="zoomAtual = true" class="block">
                        <img src="{{ $itemFoto->urlPublica() }}" alt="Foto do item"
                             class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                    </button>
                    @unless ($laudoConcluido)
                        <button type="button" wire:click="removerFoto"
                                wire:confirm="Remover esta foto? O item continua, mas será preciso tirar uma nova foto."
                                class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-gray-800 text-white flex items-center justify-center shadow"
                                aria-label="Remover foto">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endunless
                </div>
                <div x-show="zoomAtual" x-cloak @click="zoomAtual = false"
                     class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-6">
                    <img src="{{ $itemFoto->urlPublica() }}" alt="Foto do item (ampliada)" class="max-h-full max-w-full rounded-lg">
                </div>
            @endif

            @unless ($laudoConcluido)
                <label class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-600 active:bg-gray-50 cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C3.05 7.535 2.25 8.407 2.25 9.436v9.814a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25V9.436c0-1.03-.799-1.902-1.802-2.032a48.108 48.108 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                    </svg>
                    <span>{{ $itemFoto->urlPublica() ? 'Trocar foto' : 'Tirar foto' }}</span>
                    <input type="file" accept="image/*" capture="environment" wire:model="novaFoto" class="sr-only">
                </label>
            @endunless
        </div>

        <div wire:loading wire:target="novaFoto" class="mt-2 text-xs text-brand-600 flex items-center gap-1.5">
            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Analisando com IA...
        </div>

        @error('novaFoto') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Sugestão da IA (RF06 - Laudo de Saída) --}}
    @if ($itemFoto->sugestao_ia)
        <div class="mt-3 rounded-lg p-2.5 text-xs {{ $itemFoto->sugestao_ia === 'apta' ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-red-50 text-red-800 border border-red-100' }}">
            <p class="font-semibold">
                {{ $itemFoto->sugestao_ia === 'apta' ? '🟢 Sugestão da IA: Apta' : '🔴 Sugestão da IA: Não Apta' }}
            </p>
            @if ($itemFoto->parecer_ia)
                <p class="mt-1 opacity-90">{{ $itemFoto->parecer_ia }}</p>
            @endif
        </div>
    @endif

    {{-- Avaliação humana (decisão final do usuário) --}}
    @php $semFoto = ! $itemFoto->urlPublica(); @endphp
    <div class="mt-3 grid grid-cols-2 gap-2">
        <button wire:click="marcarAvaliacao('apta')" @disabled($laudoConcluido || $semFoto)
                class="py-3 rounded-lg font-semibold text-sm flex items-center justify-center gap-1.5 disabled:opacity-60 {{ $itemFoto->avaliacao->value === 'apta' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700' }}">
            🟢 Apta
        </button>
        <button wire:click="marcarAvaliacao('nao_apta')" @disabled($laudoConcluido || $semFoto)
                class="py-3 rounded-lg font-semibold text-sm flex items-center justify-center gap-1.5 disabled:opacity-60 {{ $itemFoto->avaliacao->value === 'nao_apta' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700' }}">
            🔴 Não Apta
        </button>
    </div>
    @if ($semFoto && ! $laudoConcluido)
        <p class="text-[11px] text-gray-400 mt-1.5 text-center">Tire uma foto para poder avaliar este item.</p>
    @endif
</div>
