<?php

namespace Tests\Feature;

use App\Enums\AvaliacaoItem;
use App\Enums\StatusManutencao;
use App\Livewire\Laudos\Executar;
use App\Livewire\Manutencoes\Criar as CriarManutencao;
use App\Livewire\Manutencoes\Index as ManutencoesIndex;
use App\Models\Manutencao;
use App\Models\User;
use App\Models\Vistoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManutencaoFluxoTest extends TestCase
{
    use RefreshDatabase;

    public function test_manutencoes_bloqueadas_enquanto_entrada_nao_concluido(): void
    {
        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);

        $this->assertFalse($vistoria->manutencoesHabilitadas());

        Livewire::actingAs($user)
            ->test(ManutencoesIndex::class, ['vistoria' => $vistoria])
            ->assertStatus(403);

        Livewire::actingAs($user)
            ->test(CriarManutencao::class, ['vistoria' => $vistoria])
            ->assertStatus(403);
    }

    public function test_manutencoes_habilitadas_apos_entrada_concluida(): void
    {
        $user = User::factory()->create();
        $vistoria = $this->vistoriaComEntradaConcluida($user);

        $this->assertTrue($vistoria->manutencoesHabilitadas());

        Livewire::actingAs($user)
            ->test(ManutencoesIndex::class, ['vistoria' => $vistoria])
            ->assertOk();
    }

    public function test_criar_manutencao_via_formulario(): void
    {
        $user = User::factory()->create();
        $vistoria = $this->vistoriaComEntradaConcluida($user);
        $comodo = $vistoria->laudoEntrada->comodos->first();

        Livewire::actingAs($user)
            ->test(CriarManutencao::class, ['vistoria' => $vistoria])
            ->set('comodo_id', $comodo->id)
            ->set('valor_custo', '150.00')
            ->set('descricao_defeito', 'Infiltração no teto')
            ->call('salvar')
            ->assertRedirect(route('manutencoes.index', $vistoria));

        $this->assertDatabaseHas('manutencoes', [
            'vistoria_id' => $vistoria->id,
            'comodo_id' => $comodo->id,
            'status' => StatusManutencao::EmAberto->value,
            'descricao_defeito' => 'Infiltração no teto',
        ]);
    }

    public function test_nao_permite_manutencao_em_comodo_de_outra_vistoria(): void
    {
        $user = User::factory()->create();
        $vistoria = $this->vistoriaComEntradaConcluida($user);

        $outraVistoria = $this->vistoriaComEntradaConcluida($user);
        $comodoDeOutraVistoria = $outraVistoria->laudoEntrada->comodos->first();

        Livewire::actingAs($user)
            ->test(CriarManutencao::class, ['vistoria' => $vistoria])
            ->set('comodo_id', $comodoDeOutraVistoria->id)
            ->set('valor_custo', '50.00')
            ->call('salvar')
            ->assertHasErrors(['comodo_id']);
    }

    public function test_laudo_saida_bloqueado_enquanto_houver_manutencao_em_aberto(): void
    {
        $user = User::factory()->create();
        $vistoria = $this->vistoriaComEntradaConcluida($user);
        $comodo = $vistoria->laudoEntrada->comodos->first();

        $manutencao = Manutencao::create([
            'vistoria_id' => $vistoria->id,
            'comodo_id' => $comodo->id,
            'valor_custo' => 100,
            'status' => StatusManutencao::EmAberto,
        ]);

        $this->assertTrue($vistoria->temManutencoesPendentes());
        $this->assertFalse($vistoria->fresh()->laudoSaida->podeSerIniciado());

        Livewire::actingAs($user)
            ->test(Executar::class, ['laudo' => $vistoria->laudoSaida])
            ->assertStatus(403);

        // Fechamento manual: a manutenção não muda de status sozinha.
        $this->assertEquals(StatusManutencao::EmAberto, $manutencao->fresh()->status);

        Livewire::actingAs($user)
            ->test(ManutencoesIndex::class, ['vistoria' => $vistoria])
            ->call('concluir', $manutencao->id);

        $this->assertEquals(StatusManutencao::Concluido, $manutencao->fresh()->status);
        $this->assertFalse($vistoria->fresh()->temManutencoesPendentes());
        $this->assertTrue($vistoria->fresh()->laudoSaida->podeSerIniciado());

        Livewire::actingAs($user)
            ->test(Executar::class, ['laudo' => $vistoria->laudoSaida])
            ->assertOk();
    }

    private function vistoriaComEntradaConcluida(User $user): Vistoria
    {
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala']);
        $comodo->itemFotos()->create(['avaliacao' => AvaliacaoItem::Apta, 'url_foto' => 'vistorias/1/1/foto.jpg']);
        $entrada->concluir();

        return $vistoria->fresh();
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
