# Deploy

## Manuale

cd /www/wwwroot/themedge.com/api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
systemctl restart php-fpm-84

cd ../web
git pull origin main
npm install
npm run build
pm2 restart themedge-web
