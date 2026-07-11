<div class="px-4 pt-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="p-2 -ml-2 text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 truncate">{{ $vistoria->codigo_imovel }}</h1>
    </div>

    {{-- Dados do imóvel --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500">Endereço</dt>
                <dd class="text-gray-900 text-right">{{ $vistoria->endereco }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500">Tipo</dt>
                <dd class="text-gray-900">{{ $vistoria->tipo_imovel }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500">Locatário</dt>
                <dd class="text-gray-900">{{ $vistoria->locatario }}</dd>
            </div>
            <div class="flex justify-between gap-3 pt-2 border-t border-gray-100">
                <dt class="text-gray-500">Status Geral</dt>
                <dd>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $vistoria->status_geral->badgeClass() }}">
                        {{ $vistoria->status_geral->label() }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    {{-- Análise final da IA: só existe quando a vistoria está Concluída --}}
    @if ($vistoria->status_geral === \App\Enums\StatusGeralVistoria::Concluida)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">✨ Análise Final da IA</h2>
                @if ($vistoria->assertivo_ia)
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $vistoria->assertivo_ia->badgeClass() }}">
                        {{ $vistoria->assertivo_ia->label() }}
                    </span>
                @endif
            </div>

            @if ($vistoria->parecer_ia_final)
                <p class="text-sm text-gray-700">{{ $vistoria->parecer_ia_final }}</p>
                <p class="text-[11px] text-gray-400 mt-2">Gerado em {{ $vistoria->analisado_em?->format('d/m/Y H:i') }}</p>
            @else
                <p class="text-sm text-gray-400">
                    Análise da IA indisponível para esta vistoria (verifique se a API da OpenAI está configurada).
                </p>
            @endif
        </div>
    @endif

    {{-- Fluxo: Entrada -> Manutenções -> Saída --}}
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Etapas</h2>

    <div class="space-y-3">
        @php $entrada = $vistoria->laudoEntrada; @endphp
        <a href="{{ route('laudos.executar', $entrada) }}" wire:navigate
           class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 active:bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-gray-900">Laudo de Entrada</p>
                    <p class="text-xs text-gray-500 mt-0.5">Condições no início da locação</p>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $entrada->status->badgeClass() }}">
                    {{ $entrada->status->label() }}
                </span>
            </div>
        </a>

        {{-- Manutenções: só habilita depois do Laudo de Entrada concluído --}}
        @if ($vistoria->manutencoesHabilitadas())
            @php $pendentes = $vistoria->manutencoesPendentesCount(); @endphp
            <a href="{{ route('manutencoes.index', $vistoria) }}" wire:navigate
               class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 active:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">🛠️ Manutenções da Locação</p>
                        <p class="text-xs text-gray-500 mt-0.5">Reparos registrados durante a locação</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $pendentes > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $pendentes > 0 ? $pendentes.' pendente'.($pendentes > 1 ? 's' : '') : 'Em dia' }}
                    </span>
                </div>
            </a>
        @else
            <div class="bg-gray-100 rounded-xl border border-gray-200 p-4 opacity-70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-500">🛠️ Manutenções da Locação</p>
                        <p class="text-xs text-gray-400 mt-0.5">Bloqueado até concluir a Entrada</p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
            </div>
        @endif

        @php $saida = $vistoria->laudoSaida; $liberado = $saida->podeSerIniciado(); @endphp
        @if ($liberado)
            <a href="{{ route('laudos.executar', $saida) }}" wire:navigate
               class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 active:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">Laudo de Saída</p>
                        <p class="text-xs text-gray-500 mt-0.5">Comparação com a entrada</p>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $saida->status->badgeClass() }}">
                        {{ $saida->status->label() }}
                    </span>
                </div>
            </a>
        @else
            <div class="bg-gray-100 rounded-xl border border-gray-200 p-4 opacity-70">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-500">Laudo de Saída</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            @if ($entrada->status !== \App\Enums\StatusLaudo::Concluido)
                                Bloqueado até concluir a Entrada
                            @else
                                Bloqueado por manutenções pendentes
                            @endif
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                @if ($entrada->status === \App\Enums\StatusLaudo::Concluido && $vistoria->temManutencoesPendentes())
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-3">
                        ⚠️ Existem manutenções pendentes. Conclua-as para liberar o laudo de saída.
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Documentos anexos (sempre disponível, não faz parte do fluxo sequencial) --}}
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-6 mb-3">Arquivos</h2>
    <a href="{{ route('documentos.index', $vistoria) }}" wire:navigate
       class="block bg-white rounded-xl shadow-sm border border-gray-100 p-4 active:bg-gray-50 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-900">📎 Documentos</p>
                <p class="text-xs text-gray-500 mt-0.5">Contratos, laudos externos e outros arquivos</p>
            </div>
            <span class="text-xs font-medium px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                {{ $vistoria->documentos()->count() }}
            </span>
        </div>
    </a>
</div>
