<div class="px-4 pt-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Nova Vistoria</h1>
    </div>

    <form wire:submit="salvar" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código do Imóvel</label>
            <input type="text" wire:model="codigo_imovel"
                   class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                   placeholder="Ex: AP-1024">
            @error('codigo_imovel') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
            <input type="text" wire:model="endereco"
                   class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                   placeholder="Rua, número, bairro">
            @error('endereco') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Imóvel</label>
            <div class="grid grid-cols-2 gap-3">
                <button type="button" wire:click="$set('tipo_imovel', 'Casa')"
                        class="py-3 rounded-xl border text-sm font-medium {{ $tipo_imovel === 'Casa' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-700 border-gray-300' }}">
                    Casa
                </button>
                <button type="button" wire:click="$set('tipo_imovel', 'Apartamento')"
                        class="py-3 rounded-xl border text-sm font-medium {{ $tipo_imovel === 'Apartamento' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-700 border-gray-300' }}">
                    Apartamento
                </button>
            </div>
            @error('tipo_imovel') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Locatário</label>
            <input type="text" wire:model="locatario"
                   class="w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-brand-500 focus:ring-brand-500"
                   placeholder="Nome completo">
            @error('locatario') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full py-3.5 rounded-xl bg-brand-600 text-white font-semibold shadow active:bg-accent-500"
                wire:loading.attr="disabled" wire:target="salvar">
            <span wire:loading.remove wire:target="salvar">Criar Vistoria</span>
            <span wire:loading wire:target="salvar">Salvando...</span>
        </button>
    </form>
</div>
