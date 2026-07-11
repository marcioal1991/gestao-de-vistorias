<?php

namespace Tests\Feature;

use App\Enums\AvaliacaoItem;
use App\Services\OpenAIVistoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OpenAIVistoriaServiceTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('sugestoesProvider')]
    public function test_interpreta_sugestao_da_ia_com_vies_para_nao_apta(string $sugestaoBruta, AvaliacaoItem $esperado): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('entrada.jpg', 'fake');
        Storage::disk('public')->put('saida.jpg', 'fake');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode([
                        'analise' => 'Texto qualquer.',
                        'sugestao' => $sugestaoBruta,
                    ])],
                    'finish_reason' => 'stop',
                ]],
                'usage' => [],
            ], 200),
        ]);

        $resultado = app(OpenAIVistoriaService::class)->compararFotos('entrada.jpg', 'saida.jpg');

        $this->assertEquals($esperado, $resultado['sugestao']);
    }

    public static function sugestoesProvider(): array
    {
        return [
            'apta clara' => ['Apta', AvaliacaoItem::Apta],
            'apta minusculo' => ['apta', AvaliacaoItem::Apta],
            'não apta com acento' => ['Não Apta', AvaliacaoItem::NaoApta],
            'nao apta sem acento' => ['Nao Apta', AvaliacaoItem::NaoApta],
            'reprovado (sem a palavra apta)' => ['Reprovado, há dano evidente', AvaliacaoItem::NaoApta],
            'texto ambiguo/vazio cai para nao apta' => ['', AvaliacaoItem::NaoApta],
            'texto inesperado cai para nao apta' => ['Indefinido', AvaliacaoItem::NaoApta],
        ];
    }
}
