<?php

namespace Tests\Feature;

use App\Enums\AssertividadeVistoria;
use App\Enums\AvaliacaoItem;
use App\Enums\StatusManutencao;
use App\Models\Manutencao;
use App\Models\User;
use App\Models\Vistoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnaliseFinalIaTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_analise_final_quando_vistoria_conclui(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => ['content' => json_encode([
                            'analise' => 'O laudo foi consistente entre entrada e saída.',
                            'assertivo' => 'Assertivo',
                        ])],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala']);
        $comodo->itemFotos()->create([
            'descricao_avaliacao' => 'Parede sem rachaduras',
            'avaliacao' => AvaliacaoItem::Apta,
            'url_foto' => 'vistorias/1/1/foto.jpg',
        ]);
        $entrada->concluir();

        $saida = $vistoria->laudoSaida->fresh();
        $saida->iniciarComShallowCopyDaEntrada();
        $saida->refresh();
        $saida->comodos->first()->itemFotos->first()->update(['avaliacao' => AvaliacaoItem::Apta, 'url_foto' => 'vistorias/1/1/foto-saida.jpg']);
        $saida->concluir();

        $vistoria->refresh();

        $this->assertEquals(AssertividadeVistoria::Assertivo, $vistoria->assertivo_ia);
        $this->assertEquals('O laudo foi consistente entre entrada e saída.', $vistoria->parecer_ia_final);
        $this->assertNotNull($vistoria->analisado_em);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.openai.com')
                && str_contains(json_encode($request->data()), 'Parede sem rachaduras');
        });
    }

    public function test_linha_do_tempo_inclui_entrada_manutencao_e_saida_em_ordem_cronologica(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['analise' => 'ok', 'assertivo' => 'Assertivo'])],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [],
            ], 200),
        ]);

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Cozinha']);
        $itemEntrada = $comodo->itemFotos()->create([
            'descricao_avaliacao' => 'MARCADOR_ENTRADA_TETO_OK',
            'avaliacao' => AvaliacaoItem::Apta,
            'url_foto' => 'vistorias/1/1/foto.jpg',
        ]);
        $itemEntrada->forceFill(['created_at' => now()->subDays(10)])->save();
        $entrada->concluir();

        $manutencao = Manutencao::create([
            'vistoria_id' => $vistoria->id,
            'comodo_id' => $comodo->id,
            'descricao_defeito' => 'MARCADOR_MANUTENCAO_INFILTRACAO',
            'valor_custo' => 200,
            'status' => StatusManutencao::Concluido,
        ]);
        $manutencao->forceFill(['created_at' => now()->subDays(5)])->save();

        $saida = $vistoria->laudoSaida->fresh();
        $saida->iniciarComShallowCopyDaEntrada();
        $saida->refresh();
        $itemSaida = $saida->comodos->first()->itemFotos->first();
        $itemSaida->update(['descricao_avaliacao' => 'MARCADOR_SAIDA_TETO_RECUPERADO', 'avaliacao' => AvaliacaoItem::Apta, 'url_foto' => 'vistorias/1/1/foto-saida.jpg']);
        $itemSaida->forceFill(['created_at' => now()])->save();
        $saida->concluir();

        Http::assertSent(function ($request) {
            $corpo = json_encode($request->data());
            $posEntrada = strpos($corpo, 'MARCADOR_ENTRADA_TETO_OK');
            $posManutencao = strpos($corpo, 'MARCADOR_MANUTENCAO_INFILTRACAO');
            $posSaida = strpos($corpo, 'MARCADOR_SAIDA_TETO_RECUPERADO');

            return $posEntrada !== false && $posManutencao !== false && $posSaida !== false
                && $posEntrada < $posManutencao && $posManutencao < $posSaida;
        });
    }

    public function test_falha_na_ia_nao_impede_conclusao_da_vistoria(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'falha'], 500)]);

        $user = User::factory()->create();
        $vistoria = Vistoria::criarComLaudos($this->dadosVistoria(), $user);
        $entrada = $vistoria->laudoEntrada;

        $comodo = $entrada->comodos()->create(['nome' => 'Sala']);
        $comodo->itemFotos()->create(['avaliacao' => AvaliacaoItem::Apta, 'url_foto' => 'vistorias/1/1/foto.jpg']);
        $entrada->concluir();

        $saida = $vistoria->laudoSaida->fresh();
        $saida->iniciarComShallowCopyDaEntrada();
        $saida->refresh();
        $saida->comodos->first()->itemFotos->first()->update(['avaliacao' => AvaliacaoItem::Apta, 'url_foto' => 'vistorias/1/1/foto-saida.jpg']);
        $saida->concluir();

        $vistoria->refresh();

        $this->assertEquals(\App\Enums\StatusGeralVistoria::Concluida, $vistoria->status_geral);
        $this->assertNull($vistoria->assertivo_ia);
        $this->assertNull($vistoria->parecer_ia_final);
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
