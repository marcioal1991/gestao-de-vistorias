#!/bin/bash
set -e

cd /var/www/html

if [ ! -d "vendor" ]; then
    echo ">> Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64" .env; then
    echo ">> Gerando APP_KEY..."
    php artisan key:generate --force
fi

if [ ! -d "node_modules" ]; then
    echo ">> Instalando dependências do NPM..."
    npm install
fi

if [ ! -f "public/build/manifest.json" ]; then
    echo ">> Compilando assets (Vite)..."
    npm run build
fi

echo ">> Aguardando banco de dados em ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 1
done
echo ">> Banco de dados disponível."

php artisan migrate --force

php artisan storage:link || true

chmod -R ugo+rwX storage bootstrap/cache

exec "$@"
