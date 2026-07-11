<?php

namespace App\Services;

use App\Enums\AssertividadeVistoria;
use App\Enums\AvaliacaoItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIVistoriaService
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private ?string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    public function habilitada(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * RF06 (Laudo de Entrada): analisa uma única foto e devolve uma descrição
     * das condições físicas do item, para pré-preencher o campo de texto.
     */
    public function descreverFoto(string $caminhoNoDisco): ?string
    {
        if (! $this->habilitada()) {
            Log::info('OpenAI [descreverFoto]: ignorado, API key não configurada');

            return null;
        }

        $inicio = microtime(true);
        Log::info('OpenAI [descreverFoto]: iniciando chamada', ['modelo' => $this->model, 'foto' => $caminhoNoDisco]);

        try {
            $resposta = Http::withToken($this->apiKey)
                ->timeout(280)
                ->post(self::ENDPOINT, [
                    'model' => $this->model,
                    'max_completion_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analise esta foto de vistoria imobiliária. Descreva detalhadamente em '.
                                        'português as condições físicas do que está sendo avaliado (ex: estado da '.
                                        'pintura, rachaduras, sujeira, conservação) em no máximo duas frases.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $this->paraDataUri($caminhoNoDisco)],
                                ],
                            ],
                        ],
                    ],
                ]);

            $duracao = round(microtime(true) - $inicio, 2);

            if ($resposta->failed()) {
                Log::warning('OpenAI [descreverFoto]: falha na chamada', [
                    'duracao_s' => $duracao,
                    'status' => $resposta->status(),
                    'body' => $resposta->body(),
                ]);

                return null;
            }

            if ($resposta->json('choices.0.finish_reason') === 'length') {
                Log::warning('OpenAI [descreverFoto]: resposta truncada por limite de tokens', ['duracao_s' => $duracao]);
            }

            $texto = $resposta->json('choices.0.message.content');

            Log::info('OpenAI [descreverFoto]: concluído', [
                'duracao_s' => $duracao,
                'tokens' => $resposta->json('usage'),
                'obteve_texto' => filled($texto),
            ]);

            return $texto ? trim($texto) : null;
        } catch (\Throwable $e) {
            Log::error('OpenAI [descreverFoto]: exceção', [
                'duracao_s' => round(microtime(true) - $inicio, 2),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * RF06 (Laudo de Saída): compara a foto da Entrada com a foto da Saída e retorna
     * ['analise' => string, 'sugestao' => AvaliacaoItem] com a recomendação da IA.
     *
     * @return array{analise: string, sugestao: AvaliacaoItem}|null
     */
    public function compararFotos(string $caminhoEntrada, string $caminhoSaida): ?array
    {
        if (! $this->habilitada()) {
            Log::info('OpenAI [compararFotos]: ignorado, API key não configurada');

            return null;
        }

        $prompt = <<<'PROMPT'
            Compare a foto da Entrada (Imagem 1) com a foto da Saída (Imagem 2) deste item vistoriado.
            Identifique se houve piora, novos danos, quebras ou sujeira. Retorne estritamente um objeto
            JSON com a seguinte estrutura:
            {
              "analise": "Texto em português resumindo as diferenças ou confirmando que está igual",
              "sugestao": "Apta ou Não Apta (retorne 'Não Apta' se houver danos evidentes causados na saída, caso contrário retorne 'Apta')"
            }
            PROMPT;

        $inicio = microtime(true);
        Log::info('OpenAI [compararFotos]: iniciando chamada', [
            'modelo' => $this->model,
            'foto_entrada' => $caminhoEntrada,
            'foto_saida' => $caminhoSaida,
        ]);

        try {
            $resposta = Http::withToken($this->apiKey)
                ->timeout(280)
                ->post(self::ENDPOINT, [
                    'model' => $this->model,
                    'max_completion_tokens' => 1200,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $prompt],
                                ['type' => 'image_url', 'image_url' => ['url' => $this->paraDataUri($caminhoEntrada)]],
                                ['type' => 'image_url', 'image_url' => ['url' => $this->paraDataUri($caminhoSaida)]],
                            ],
                        ],
                    ],
                ]);

            $duracao = round(microtime(true) - $inicio, 2);

            if ($resposta->failed()) {
                Log::warning('OpenAI [compararFotos]: falha na chamada', [
                    'duracao_s' => $duracao,
                    'status' => $resposta->status(),
                    'body' => $resposta->body(),
                ]);

                return null;
            }

            if ($resposta->json('choices.0.finish_reason') === 'length') {
                Log::warning('OpenAI [compararFotos]: resposta truncada por limite de tokens', ['duracao_s' => $duracao]);
            }

            $conteudo = $resposta->json('choices.0.message.content');
            $dados = json_decode((string) $conteudo, true);

            Log::info('OpenAI [compararFotos]: concluído', [
                'duracao_s' => $duracao,
                'tokens' => $resposta->json('usage'),
            ]);

            if (! is_array($dados) || ! isset($dados['analise'], $dados['sugestao'])) {
                Log::warning('OpenAI [compararFotos]: resposta em formato inesperado', ['content' => $conteudo]);

                return null;
            }

            $sugestaoTexto = Str::lower((string) $dados['sugestao']);
            $sugestao = Str::contains($sugestaoTexto, 'não') || Str::contains($sugestaoTexto, 'nao')
                ? AvaliacaoItem::NaoApta
                : AvaliacaoItem::Apta;

            return [
                'analise' => (string) $dados['analise'],
                'sugestao' => $sugestao,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI [compararFotos]: exceção', [
                'duracao_s' => round(microtime(true) - $inicio, 2),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Módulo de Manutenção: analisa a foto do defeito/reparo e devolve uma
     * descrição técnica curta para pré-preencher o campo do relatório.
     */
    public function descreverDefeitoManutencao(string $caminhoNoDisco): ?string
    {
        if (! $this->habilitada()) {
            Log::info('OpenAI [descreverDefeitoManutencao]: ignorado, API key não configurada');

            return null;
        }

        $inicio = microtime(true);
        Log::info('OpenAI [descreverDefeitoManutencao]: iniciando chamada', ['modelo' => $this->model, 'foto' => $caminhoNoDisco]);

        try {
            $resposta = Http::withToken($this->apiKey)
                ->timeout(280)
                ->post(self::ENDPOINT, [
                    'model' => $this->model,
                    'max_completion_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Analise esta foto enviada pelo vistoriador durante o período de '.
                                        'manutenção do imóvel. Identifique o defeito físico ou problema de '.
                                        'conservação visível na imagem (ex: infiltração, telha quebrada, fiação '.
                                        'exposta, trinco quebrado) e gere uma descrição técnica e direta em '.
                                        'português, com no máximo 15 palavras, para preencher o relatório de reparos.',
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $this->paraDataUri($caminhoNoDisco)],
                                ],
                            ],
                        ],
                    ],
                ]);

            $duracao = round(microtime(true) - $inicio, 2);

            if ($resposta->failed()) {
                Log::warning('OpenAI [descreverDefeitoManutencao]: falha na chamada', [
                    'duracao_s' => $duracao,
                    'status' => $resposta->status(),
                    'body' => $resposta->body(),
                ]);

                return null;
            }

            if ($resposta->json('choices.0.finish_reason') === 'length') {
                Log::warning('OpenAI [descreverDefeitoManutencao]: resposta truncada por limite de tokens', ['duracao_s' => $duracao]);
            }

            $texto = $resposta->json('choices.0.message.content');

            Log::info('OpenAI [descreverDefeitoManutencao]: concluído', [
                'duracao_s' => $duracao,
                'tokens' => $resposta->json('usage'),
                'obteve_texto' => filled($texto),
            ]);

            return $texto ? trim($texto) : null;
        } catch (\Throwable $e) {
            Log::error('OpenAI [descreverDefeitoManutencao]: exceção', [
                'duracao_s' => round(microtime(true) - $inicio, 2),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ao concluir a vistoria (Entrada + Saída concluídos), avalia a linha do tempo
     * cronológica completa (descrições da Entrada, manutenções e Saída) e devolve
     * um parecer sobre se o laudo foi assertivo.
     *
     * @return array{analise: string, assertivo: AssertividadeVistoria}|null
     */
    public function avaliarVistoriaCompleta(string $linhaDoTempo): ?array
    {
        if (! $this->habilitada()) {
            Log::info('OpenAI [avaliarVistoriaCompleta]: ignorado, API key não configurada');

            return null;
        }

        if (blank(trim($linhaDoTempo))) {
            Log::info('OpenAI [avaliarVistoriaCompleta]: ignorado, sem eventos para analisar');

            return null;
        }

        $prompt = <<<PROMPT
            Você é um auditor de qualidade de vistorias imobiliárias. Abaixo está a linha do tempo
            completa e cronológica de uma vistoria já concluída: as avaliações do Laudo de Entrada,
            as manutenções realizadas durante o período de locação, e as avaliações do Laudo de Saída.

            {$linhaDoTempo}

            Analise se este laudo foi ASSERTIVO: as descrições são consistentes e específicas (não
            genéricas ou vazias), os problemas identificados na entrada foram devidamente
            acompanhados pelas manutenções e reavaliados na saída, e não há contradições ou lacunas
            evidentes entre as etapas. Retorne estritamente um objeto JSON com a seguinte estrutura:
            {
              "analise": "Texto em português resumindo os pontos fortes e fracos do laudo, em até 4 frases",
              "assertivo": "Assertivo ou Não Assertivo"
            }
            PROMPT;

        $inicio = microtime(true);
        Log::info('OpenAI [avaliarVistoriaCompleta]: iniciando chamada', ['modelo' => $this->model]);

        try {
            $resposta = Http::withToken($this->apiKey)
                ->timeout(280)
                ->post(self::ENDPOINT, [
                    'model' => $this->model,
                    'max_completion_tokens' => 1500,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            $duracao = round(microtime(true) - $inicio, 2);

            if ($resposta->failed()) {
                Log::warning('OpenAI [avaliarVistoriaCompleta]: falha na chamada', [
                    'duracao_s' => $duracao,
                    'status' => $resposta->status(),
                    'body' => $resposta->body(),
                ]);

                return null;
            }

            if ($resposta->json('choices.0.finish_reason') === 'length') {
                Log::warning('OpenAI [avaliarVistoriaCompleta]: resposta truncada por limite de tokens', ['duracao_s' => $duracao]);
            }

            $conteudo = $resposta->json('choices.0.message.content');
            $dados = json_decode((string) $conteudo, true);

            Log::info('OpenAI [avaliarVistoriaCompleta]: concluído', [
                'duracao_s' => $duracao,
                'tokens' => $resposta->json('usage'),
            ]);

            if (! is_array($dados) || ! isset($dados['analise'], $dados['assertivo'])) {
                Log::warning('OpenAI [avaliarVistoriaCompleta]: resposta em formato inesperado', ['content' => $conteudo]);

                return null;
            }

            $assertivoTexto = Str::lower((string) $dados['assertivo']);
            $assertivo = Str::contains($assertivoTexto, 'não') || Str::contains($assertivoTexto, 'nao')
                ? AssertividadeVistoria::NaoAssertivo
                : AssertividadeVistoria::Assertivo;

            return [
                'analise' => (string) $dados['analise'],
                'assertivo' => $assertivo,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI [avaliarVistoriaCompleta]: exceção', [
                'duracao_s' => round(microtime(true) - $inicio, 2),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function paraDataUri(string $caminhoNoDisco): string
    {
        $conteudo = Storage::disk('public')->get($caminhoNoDisco);
        $mime = Storage::disk('public')->mimeType($caminhoNoDisco) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($conteudo);
    }
}
