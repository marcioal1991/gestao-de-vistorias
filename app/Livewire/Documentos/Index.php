<?php

namespace App\Livewire\Documentos;

use App\Models\Documento;
use App\Models\Vistoria;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;

    /**
     * Extensões aceitas para anexos gerais da vistoria (texto, PDF, planilhas, etc.).
     * Propositalmente não inclui tipos executáveis/script (.exe, .php, .js, .html...).
     */
    public const EXTENSOES_ACEITAS = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'xls', 'xlsx', 'csv', 'ppt', 'pptx'];

    public Vistoria $vistoria;

    public $novoArquivo = null;

    public function mount(Vistoria $vistoria): void
    {
        abort_unless($vistoria->user_id === auth()->id(), 403);

        $this->vistoria = $vistoria;
    }

    public function updatedNovoArquivo(): void
    {
        $this->validate([
            'novoArquivo' => [
                'required',
                'file',
                'mimes:'.implode(',', self::EXTENSOES_ACEITAS),
                'max:20480',
            ],
        ]);

        $caminho = $this->novoArquivo->store("documentos/{$this->vistoria->id}", 'public');

        Documento::create([
            'vistoria_id' => $this->vistoria->id,
            'usuario_id' => auth()->id(),
            'nome_original' => $this->novoArquivo->getClientOriginalName(),
            'caminho_arquivo' => $caminho,
            'tipo_mime' => $this->novoArquivo->getMimeType(),
            'tamanho' => $this->novoArquivo->getSize(),
        ]);

        Log::info('Documento: upload concluído', [
            'vistoria_id' => $this->vistoria->id,
            'caminho' => $caminho,
        ]);

        $this->novoArquivo = null;
    }

    public function remover(int $documentoId): void
    {
        $documento = $this->vistoria->documentos()->findOrFail($documentoId);

        Storage::disk('public')->delete($documento->caminho_arquivo);

        Log::info('Documento: removido', ['documento_id' => $documento->id, 'vistoria_id' => $this->vistoria->id]);

        $documento->delete();
    }

    public function render(): View
    {
        return view('livewire.documentos.index', [
            'documentos' => $this->vistoria->documentos()->latest()->get(),
        ]);
    }
}
