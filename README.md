# Gestão de Vistorias (POC)

Aplicação mobile-first para gestão de vistorias de imóveis (Entrada/Saída), com comparação visual de fotos e análise assistida por IA (OpenAI Vision).

Stack: Laravel 13 + Livewire 3 (Volt para as telas de auth) + Tailwind CSS + PostgreSQL, rodando em Docker.

## Subir o ambiente

```bash
cp .env.example .env   # se ainda não existir
docker compose up -d --build
```

A aplicação estará em **http://localhost:3000** (porta configurável via `APP_PORT` no `.env`).

No primeiro `up`, o container `app` automaticamente:
- instala dependências do Composer e do NPM;
- compila os assets (Vite/Tailwind);
- aguarda o Postgres ficar pronto e roda as migrations;
- cria o link `public/storage` para as fotos.

## Configurar a OpenAI

No `.env`, defina:

```
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```

Sem a chave configurada, a aplicação funciona normalmente — os campos de descrição e a sugestão da IA simplesmente não são preenchidos automaticamente, e a decisão do vistoriador (Apta/Não Apta) continua manual.

## Uso

1. Acesse `/register` e crie uma conta.
2. No Dashboard, toque no botão "+" para criar uma vistoria.
3. Abra o Laudo de Entrada, cadastre cômodos e capture fotos (a câmera do celular abre nativamente).
4. Marque todos os itens como "Apta" para poder concluir o laudo.
5. Com o Laudo de Entrada concluído, o Laudo de Saída é liberado e já vem com os cômodos/descrições clonados (sem as fotos). Capture as novas fotos para comparar com a entrada.

## Testes

```bash
docker compose exec app php artisan test
```

## Estrutura de domínio

`Vistoria` → dois `Laudo` (Entrada/Saída) → vários `Comodo` → vários `ItemFoto`. Regras de negócio (bloqueio do laudo de saída, shallow copy, conclusão de laudo/vistoria) estão centralizadas nos models `App\Models\Laudo` e `App\Models\Vistoria`. A integração com a OpenAI está isolada em `App\Services\OpenAIVistoriaService`.
