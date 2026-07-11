<?php

namespace Tests\Feature;

use App\Enums\AvaliacaoItem;
use App\Enums\StatusGeralVistoria;
use App\Enums\StatusLaudo;
use App\Enums\TipoLaudo;
use App\Models\User;
use App\Models\Vistoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VistoriaFluxoTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_vistoria_gera_dois_laudos_pendentes(): void
    {
        $user = User::factory()->create();

        $vistoria = Vistoria::criarComLaudos([
            'codigo_imovel' => 'AP-1024',
            'endereco' => 'Rua das Flores, 123',
            'tipo_imovel' => 'Apartamento',
            'locatario' => 'João da Silva',
        ], $user);

        $this->assertCount(2, $vistoria->laudos);
        $this->assertEquals(StatusGeralVistoria::EmAndamento, $vistoria->status_geral);
        $this->assertEquals(StatusLaudo::Pendente, $vistoria->laudoEntrada->status);
        $this->assertEquals(StatusLaudo::Pendente, $vistoria->laudoSaida->status);
    }

    public function test_laudo_saida_bloqueado_enquanto_entrada_nao_concluido(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        $this->assertFalse($vistoria->laudoSaida->podeSerIniciado());

        Livewire::actingAs($user)
            ->test(\App\Livewire\Laudos\Executar::class, ['laudo' => $vistoria->laudoSaida])
            ->assertStatus(403);
    }

    public function test_laudo_so_conclui_quando_todos_os_itens_estao_aptos(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala', 'descricao' => 'Pintura fosca']);
        $item = $comodo->itemFotos()->create(['descricao_avaliacao' => 'Parede sem rachaduras']);

        $this->assertFalse($entrada->podeSerConcluido());
        $this->assertFalse($entrada->concluir());

        $item->update(['avaliacao' => AvaliacaoItem::Apta]);

        $this->assertTrue($entrada->fresh()->podeSerConcluido());
        $this->assertTrue($entrada->concluir());
        $this->assertEquals(StatusLaudo::Concluido, $entrada->fresh()->status);
    }

    public function test_shallow_copy_clona_comodos_sem_copiar_fotos(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Cozinha', 'descricao' => 'Azulejos brancos']);
        $item = $comodo->itemFotos()->create([
            'descricao_avaliacao' => 'Azulejo sem trincas',
            'url_foto' => 'vistorias/1/1/foto-entrada.jpg',
            'avaliacao' => AvaliacaoItem::Apta,
        ]);
        $entrada->concluir();

        $saida = $vistoria->laudoSaida->fresh();
        $this->assertTrue($saida->podeSerIniciado());

        $saida->iniciarComShallowCopyDaEntrada();
        $saida->refresh();

        $this->assertCount(1, $saida->comodos);
        $comodoSaida = $saida->comodos->first();
        $this->assertEquals('Cozinha', $comodoSaida->nome);

        $itemSaida = $comodoSaida->itemFotos->first();
        $this->assertEquals('Azulejo sem trincas', $itemSaida->descricao_avaliacao);
        $this->assertNull($itemSaida->url_foto);
        $this->assertEquals(AvaliacaoItem::Pendente, $itemSaida->avaliacao);
        $this->assertEquals($item->id, $itemSaida->foto_entrada_referencia_id);
    }

    public function test_vistoria_conclui_automaticamente_quando_ambos_laudos_concluidos(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Quarto']);
        $comodo->itemFotos()->create(['avaliacao' => AvaliacaoItem::Apta]);
        $entrada->concluir();

        $saida = $vistoria->laudoSaida->fresh();
        $saida->iniciarComShallowCopyDaEntrada();
        $saida->refresh();

        $itemSaida = $saida->comodos->first()->itemFotos->first();
        $itemSaida->update(['avaliacao' => AvaliacaoItem::Apta]);

        $this->assertTrue($saida->fresh()->concluir());
        $this->assertEquals(StatusGeralVistoria::Concluida, $vistoria->fresh()->status_geral);
    }

    public function test_dashboard_mostra_apenas_vistorias_do_usuario_logado(): void
    {
        $user = User::factory()->create();
        $outroUsuario = User::factory()->create();

        Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        Vistoria::criarComLaudos($this->dadosVistoria(), $outroUsuario);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertCount(1, $user->fresh()->vistorias);
    }

    public function test_remover_item_apaga_o_registro_e_o_arquivo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala']);
        \Illuminate\Support\Facades\Storage::disk('public')->put('vistorias/1/1/foto.jpg', 'conteudo-fake');
        $item = $comodo->itemFotos()->create([
            'url_foto' => 'vistorias/1/1/foto.jpg',
            'avaliacao' => AvaliacaoItem::Pendente,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Laudos\ItemFotoCard::class, ['itemFoto' => $item])
            ->call('removerItem');

        $this->assertModelMissing($item);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('vistorias/1/1/foto.jpg');
    }

    public function test_remover_foto_mantem_o_item_mas_limpa_a_foto_e_avaliacao(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala']);
        $item = $comodo->itemFotos()->create([
            'descricao_avaliacao' => 'Parede sem rachaduras',
            'url_foto' => 'vistorias/1/1/foto.jpg',
            'avaliacao' => AvaliacaoItem::Apta,
            'parecer_ia' => 'Parecer qualquer',
            'sugestao_ia' => 'apta',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Laudos\ItemFotoCard::class, ['itemFoto' => $item])
            ->call('removerFoto');

        $item->refresh();
        $this->assertNotNull(\App\Models\ItemFoto::find($item->id));
        $this->assertNull($item->url_foto);
        $this->assertNull($item->parecer_ia);
        $this->assertNull($item->sugestao_ia);
        $this->assertEquals(AvaliacaoItem::Pendente, $item->avaliacao);
        $this->assertEquals('Parede sem rachaduras', $item->descricao_avaliacao);
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
