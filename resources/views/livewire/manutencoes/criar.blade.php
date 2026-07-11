<div class="px-4 pt-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('manutencoes.index', $vistoria) }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Nova Manutenção</h1>
    </div>

    <form wire:submit="salvar" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cômodo</label>
            <select wire:model="comodo_id"
                    class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500">
                <option value="">Selecione...</option>
                @foreach ($comodos as $comodo)
                    <option value="{{ $comodo->id }}">{{ $comodo->nome }}</option>
                @endforeach
            </select>
            @error('comodo_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto do defeito</label>

            <div class="flex items-center gap-3">
                @if ($caminhoFoto)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($caminhoFoto) }}" alt="Foto do defeito"
                         class="h-20 w-20 object-cover rounded-lg border border-gray-200 shrink-0">
                @endif

                <label class="flex-1 flex items-center justify-center gap-2 py-3 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-600 active:bg-gray-50 cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C3.05 7.535 2.25 8.407 2.25 9.436v9.814a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25V9.436c0-1.03-.799-1.902-1.802-2.032a48.108 48.108 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                    </svg>
                    <span>{{ $caminhoFoto ? 'Trocar foto' : 'Tirar foto' }}</span>
                    <input type="file" accept="image/*" capture="environment" wire:model="foto" class="sr-only">
                </label>
            </div>

            <div wire:loading wire:target="foto" class="mt-2 text-xs text-brand-600 flex items-center gap-1.5">
                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Analisando com IA...
            </div>

            @error('foto') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Custo estimado</label>
            <div x-data="{
                    centavos: {{ (int) round(((float) $valor_custo) * 100) }},
                    get formatado() {
                        return (this.centavos / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                    digitar(e) {
                        const digitos = e.target.value.replace(/\D/g, '');
                        this.centavos = parseInt(digitos || '0', 10);
                        $wire.set('valor_custo', (this.centavos / 100).toFixed(2));
                    },
                 }"
                 class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-base">R$</span>
                <input type="text" inputmode="numeric" :value="formatado" @input="digitar"
                       class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 pl-11 pr-4 focus:border-brand-500 focus:ring-brand-500"
                       placeholder="0,00">
            </div>
            @error('valor_custo') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do defeito</label>
            <textarea wire:model="descricao_defeito" rows="3"
                      class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                      placeholder="Descreva o problema encontrado"></textarea>
            @error('descricao_defeito') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full py-3.5 rounded-xl bg-brand-600 text-white font-semibold shadow active:bg-accent-500"
                wire:loading.attr="disabled" wire:target="salvar">
            <span wire:loading.remove wire:target="salvar">Salvar Manutenção</span>
            <span wire:loading wire:target="salvar">Salvando...</span>
        </button>
    </form>
</div>
