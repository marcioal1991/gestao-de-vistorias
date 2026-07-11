<div class="px-4 pt-4 pb-10">
    <div class="flex items-center gap-2 mb-1">
        <a href="{{ route('vistorias.show', $vistoria) }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">📎 Documentos</h1>
    </div>
    <p class="text-xs text-gray-500 mb-4 ml-9">{{ $vistoria->codigo_imovel }} &middot; {{ $vistoria->endereco }}</p>

    {{-- Upload --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <label class="flex flex-col items-center justify-center gap-2 py-6 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-600 active:bg-gray-50 cursor-pointer">
            <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l-3.75 3.75M12 9.75l3.75 3.75M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
            </svg>
            <span>Selecionar arquivo</span>
            <span class="text-[11px] text-gray-400">PDF, Word, Excel, PowerPoint, TXT — até 20MB</span>
            <input type="file" wire:model="novoArquivo"
                   accept=".pdf,.doc,.docx,.txt,.rtf,.odt,.xls,.xlsx,.csv,.ppt,.pptx"
                   class="sr-only">
        </label>

        <div wire:loading wire:target="novoArquivo" class="mt-3 text-xs text-brand-600 flex items-center gap-1.5 justify-center">
            <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Enviando arquivo...
        </div>

        @error('novoArquivo') <p class="text-xs text-red-600 mt-2 text-center">{{ $message }}</p> @enderror
    </div>

    {{-- Listagem --}}
    <div class="space-y-3">
        @forelse ($documentos as $documento)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
                <span class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-700 text-[10px] font-bold uppercase">
                    {{ $documento->extensao() }}
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $documento->nome_original }}</p>
                    <p class="text-xs text-gray-500">{{ $documento->tamanhoFormatado() }} &middot; {{ $documento->created_at->format('d/m/Y H:i') }}</p>
                </div>

                <a href="{{ $documento->urlPublica() }}" target="_blank" rel="noopener"
                   class="shrink-0 p-2 text-brand-600" aria-label="Baixar">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 12m0 0l4.5-4.5M12 12V3" />
                    </svg>
                </a>

                <button type="button" wire:click="remover({{ $documento->id }})"
                        wire:confirm="Remover este documento? Essa ação não pode ser desfeita."
                        class="shrink-0 p-2 text-red-600" aria-label="Remover">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @empty
            <div class="text-center py-16">
                <p class="text-gray-500">Nenhum documento anexado ainda.</p>
                <p class="text-sm text-gray-400 mt-1">Selecione um arquivo acima para anexar.</p>
            </div>
        @endforelse
    </div>
</div>
