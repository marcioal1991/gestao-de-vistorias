<?php

namespace App\Livewire\Manutencoes;

use App\Enums\StatusManutencao;
use App\Models\Manutencao;
use App\Models\Vistoria;
use App\Services\OpenAIVistoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Criar extends Component
{
    use WithFileUploads;

    public Vistoria $vistoria;

    public ?int $comodo_id = null;

    public $foto = null;

    public string $caminhoFoto = '';

    public string $descricao_defeito = '';

    public string $valor_custo = '0.00';

    public bool $analisandoIa = false;

    public function mount(Vistoria $vistoria): void
    {
        abort_unless($vistoria->user_id === auth()->id(), 403);
        abort_unless($vistoria->manutencoesHabilitadas(), 403, 'A aba de Manutenções só é liberada após o Laudo de Entrada ser concluído.');

        $this->vistoria = $vistoria;
    }

    public function updatedFoto(OpenAIVistoriaService $ia): void
    {
        $this->validate([
            'foto' => ['image', 'max:102400'],
        ]);

        $this->caminhoFoto = $this->foto->store("manutencoes/{$this->vistoria->id}", 'public');
        $this->foto = null;

        Log::info('ManutencaoCriar: foto salva no disco', ['vistoria_id' => $this->vistoria->id, 'caminho' => $this->caminhoFoto]);

        $this->analisandoIa = true;

        try {
            $descricao = $ia->descreverDefeitoManutencao($this->caminhoFoto);

            if ($descricao) {
                $this->descricao_defeito = $descricao;
            }
        } catch (\Throwable $e) {
            Log::error('ManutencaoCriar: exceção inesperada durante análise de IA', [
                'vistoria_id' => $this->vistoria->id,
                'message' => $e->getMessage(),
            ]);
        } finally {
            $this->analisandoIa = false;
        }
    }

    public function salvar(): void
    {
        $comodoIds = $this->vistoria->laudoEntrada->comodos()->pluck('id');

        $dados = $this->validate([
            'comodo_id' => ['required', 'integer', 'in:'.$comodoIds->implode(',')],
            'valor_custo' => ['required', 'numeric', 'min:0'],
            'descricao_defeito' => ['nullable', 'string', 'max:1000'],
        ]);

        Manutencao::create([
            'vistoria_id' => $this->vistoria->id,
            'comodo_id' => $dados['comodo_id'],
            'url_foto' => $this->caminhoFoto ?: null,
            'descricao_defeito' => $dados['descricao_defeito'],
            'valor_custo' => $dados['valor_custo'],
            'status' => StatusManutencao::EmAberto,
        ]);

        session()->flash('sucesso', 'Manutenção registrada com sucesso!');

        $this->redirect(route('manutencoes.index', $this->vistoria), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.manutencoes.criar', [
            'comodos' => $this->vistoria->laudoEntrada->comodos,
        ]);
    }
}
