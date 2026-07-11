<?php

namespace App\Livewire\Vistorias;

use App\Enums\StatusGeralVistoria;
use App\Models\Vistoria;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Criar extends Component
{
    public string $codigo_imovel = '';

    public string $endereco = '';

    public string $tipo_imovel = 'Apartamento';

    public string $locatario = '';

    protected function rules(): array
    {
        return [
            'codigo_imovel' => [
                'required', 'string', 'max:50',
                Rule::unique('vistorias', 'codigo_imovel')
                    ->where('status_geral', StatusGeralVistoria::EmAndamento->value),
            ],
            'endereco' => ['required', 'string', 'max:255'],
            'tipo_imovel' => ['required', 'in:Casa,Apartamento'],
            'locatario' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'codigo_imovel.unique' => 'Já existe uma vistoria em andamento para este imóvel.',
        ];
    }

    /**
     * RF03: ao salvar, cria a vistoria e já vincula os dois laudos (Entrada e Saída).
     */
    public function salvar(): void
    {
        $dados = $this->validate();

        $vistoria = Vistoria::criarComLaudos($dados, auth()->user());

        session()->flash('sucesso', 'Vistoria criada com sucesso!');

        $this->redirect(route('vistorias.show', $vistoria), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.vistorias.criar');
    }
}
