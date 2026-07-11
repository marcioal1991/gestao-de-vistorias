<div class="px-4 pt-4 pb-28">
    <div class="flex items-center gap-2 mb-1">
        <a href="{{ route('vistorias.show', $laudo->vistoria) }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Laudo de {{ $laudo->tipo->label() }}</h1>
    </div>
    <p class="text-xs text-gray-500 mb-4 ml-9">{{ $laudo->vistoria->codigo_imovel }} &middot; {{ $laudo->vistoria->endereco }}</p>

    <div class="mb-4">
        <span class="text-xs font-medium px-2 py-1 rounded-full {{ $laudo->status->badgeClass() }}">
            {{ $laudo->status->label() }}
        </span>
    </div>

    @if ($ehSaida)
        <div class="bg-brand-50 border border-brand-100 text-brand-800 text-xs rounded-xl p-3 mb-4">
            Os cômodos e descrições foram copiados do Laudo de Entrada. Compare a foto antiga com a nova foto de saída em cada item.
        </div>
    @endif

    @php $estaConcluido = $laudo->status === \App\Enums\StatusLaudo::Concluido; @endphp
    @unless ($estaConcluido)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl p-3 mb-4 flex gap-2">
            <span class="text-base leading-none">⚠️</span>
            <span>
                <strong>Todas as fotos precisam estar marcadas como "Apta"</strong> para que este laudo possa ser concluído.
                Se algum item ficar "Não Apta" ou pendente, a conclusão fica bloqueada.
                @if ($totalItens > 0)
                    <span class="block mt-1 font-medium">{{ $itensAptos }} de {{ $totalItens }} itens aptos.</span>
                @endif
            </span>
        </div>
    @endunless

    @if ($erroConclusao)
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3 mb-4">
            {{ $erroConclusao }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($laudo->comodos as $comodo)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="mb-3">
                    <p class="font-semibold text-gray-900">{{ $comodo->nome }}</p>
                    @if ($comodo->descricao)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $comodo->descricao }}</p>
                    @endif
                </div>

                <div class="space-y-3">
                    @foreach ($comodo->itemFotos as $itemFoto)
                        <livewire:laudos.item-foto-card
                            :item-foto="$itemFoto"
                            :laudo-concluido="$estaConcluido"
                            :key="'item-'.$itemFoto->id" />
                    @endforeach
                </div>

                @unless ($estaConcluido)
                    <button wire:click="adicionarItem({{ $comodo->id }})"
                            class="mt-3 w-full py-2.5 rounded-lg border border-dashed border-gray-300 text-sm text-gray-600 active:bg-gray-50">
                        + Adicionar item de avaliação
                    </button>
                @endunless
            </div>
        @empty
            <div class="text-center py-10">
                <p class="text-gray-500 text-sm">Nenhum cômodo cadastrado ainda.</p>
            </div>
        @endforelse
    </div>

    @unless ($estaConcluido)
        <div class="mt-4">
            @if ($mostrarFormComodo)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Cômodo</label>
                        <input type="text" wire:model="novoComodoNome"
                               class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                               placeholder="Ex: Sala, Cozinha">
                        @error('novoComodoNome') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea wire:model="novoComodoDescricao" rows="2"
                                  class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                                  placeholder="Ex: Pintura fosca, piso laminado"></textarea>
                        @error('novoComodoDescricao') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="$set('mostrarFormComodo', false)"
                                class="flex-1 py-3 rounded-xl border border-gray-300 text-gray-700 font-medium">
                            Cancelar
                        </button>
                        <button wire:click="adicionarComodo"
                                class="flex-1 py-3 rounded-xl bg-brand-600 text-white font-semibold active:bg-accent-500">
                            Adicionar
                        </button>
                    </div>
                </div>
            @else
                <button wire:click="$set('mostrarFormComodo', true)"
                        class="w-full py-3.5 rounded-xl border-2 border-dashed border-brand-300 text-brand-600 font-medium active:bg-brand-50">
                    + Adicionar Cômodo
                </button>
            @endif
        </div>
    @endunless

    @unless ($estaConcluido)
        <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-200">
            <button wire:click="concluirLaudo" wire:loading.attr="disabled" wire:target="concluirLaudo"
                    class="w-full py-3.5 rounded-xl font-semibold shadow {{ $podeSerConcluido ? 'bg-emerald-600 text-white active:bg-emerald-700' : 'bg-gray-200 text-gray-500' }}">
                Concluir Laudo
            </button>
            @unless ($podeSerConcluido)
                <p class="text-xs text-gray-500 text-center mt-2">
                    @if ($totalItens === 0)
                        Cadastre ao menos um item de avaliação para concluir.
                    @else
                        {{ $itensPendentes }} {{ $itensPendentes === 1 ? 'item ainda não está' : 'itens ainda não estão' }} marcado(s) como "Apta".
                    @endif
                </p>
            @endunless
        </div>
    @endunless
</div>
