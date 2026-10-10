# Setup Themedge

## Requisiti

- Ubuntu 22.04+
- Nginx 1.24+
- PHP 8.4+
- MySQL 8+
- Redis 7+
- Node.js 20+
- Composer 2.6+
- Meilisearch 1.5+

## Backend

cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan scout:import "App\Models\Article"

## Frontend

cd web
npm install
cp .env.example .env.local
npm run build
pm2 start npm --name "themedge-web" -- start
