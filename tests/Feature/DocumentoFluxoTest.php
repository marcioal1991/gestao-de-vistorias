<?php

namespace Tests\Feature;

use App\Livewire\Documentos\Index as DocumentosIndex;
use App\Models\User;
use App\Models\Vistoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentoFluxoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_enviar_um_documento_valido(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        $arquivo = UploadedFile::fake()->create('contrato.pdf', 500, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->set('novoArquivo', $arquivo)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('documentos', [
            'vistoria_id' => $vistoria->id,
            'usuario_id' => $user->id,
            'nome_original' => 'contrato.pdf',
        ]);

        $documento = $vistoria->documentos()->first();
        Storage::disk('public')->assertExists($documento->caminho_arquivo);
    }

    public function test_rejeita_tipo_de_arquivo_nao_permitido(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        $arquivo = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

        Livewire::actingAs($user)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->set('novoArquivo', $arquivo)
            ->assertHasErrors(['novoArquivo']);

        $this->assertDatabaseCount('documentos', 0);
    }

    public function test_rejeita_arquivo_maior_que_o_limite(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        // 20480 KB é o limite; 21000 KB deve estourar.
        $arquivo = UploadedFile::fake()->create('grande.pdf', 21000, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->set('novoArquivo', $arquivo)
            ->assertHasErrors(['novoArquivo']);

        $this->assertDatabaseCount('documentos', 0);
    }

    public function test_usuario_pode_remover_documento_e_arquivo_e_apagado(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        $arquivo = UploadedFile::fake()->create('contrato.pdf', 200, 'application/pdf');

        Livewire::actingAs($user)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->set('novoArquivo', $arquivo);

        $documento = $vistoria->documentos()->first();
        $caminho = $documento->caminho_arquivo;
        Storage::disk('public')->assertExists($caminho);

        Livewire::actingAs($user)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->call('remover', $documento->id);

        $this->assertDatabaseMissing('documentos', ['id' => $documento->id]);
        Storage::disk('public')->assertMissing($caminho);
    }

    public function test_outro_usuario_nao_acessa_documentos_de_vistoria_alheia(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $dono);

        Livewire::actingAs($outro)
            ->test(DocumentosIndex::class, ['vistoria' => $vistoria])
            ->assertStatus(403);
    }

    private function dadosVistoria(): array
    {
        return [
            'codigo_imovel' => 'AP-'.fake()->numberBetween(1000, 9999),
            'endereco' => 'Rua de Teste, 100',
            'tipo_imovel' => 'Apartamento',
            'locatario' => 'Locatário Teste',
        ];
    }
}
