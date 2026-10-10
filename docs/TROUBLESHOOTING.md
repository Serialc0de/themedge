# 🔧 Troubleshooting — Problemi Noti e Fix

Raccolta dei problemi riscontrati e delle soluzioni.

---

## 🚨 Sito non funziona (502 Bad Gateway)

Sintomo: tutte le pagine PHP danno 502.

Causa: PHP-FPM crashato.

Fix:

# 1. Verifica
ps aux | grep php-fpm | grep -v grep
ls -la /tmp/php-cgi-84.sock

# 2. Recovery
pkill -9 -f "php-fpm: master"
pkill -9 -f "php-fpm: pool"
rm -f /tmp/php-cgi-84.sock
/www/server/php/84/sbin/php-fpm --daemonize
sleep 2

# 3. Verifica
curl -sI https://admin.themedge.com/admin/login | head -3

Vedi PHP_FPM.md per i dettagli.

---

## 🔴 PHP-FPM crash loop

Sintomo:

ERROR: Another FPM instance seems to already listen on /tmp/php-cgi-84.sock
ERROR: FPM initialization failed

Causa: tentativo di restart con systemctl (che è in conflitto).

Fix:

# 1. Stop di systemd
systemctl stop php-fpm-84 2>/dev/null

# 2. Uccidi tutti i processi
pkill -QUIT -f "php-fpm: master" 2>/dev/null
sleep 2
pkill -9 -f "php-fpm: master" 2>/dev/null
pkill -9 -f "php-fpm: pool" 2>/dev/null

# 3. Rimuovi socket
rm -f /tmp/php-cgi-84.sock

# 4. Assicurati che systemd sia masked
systemctl mask php-fpm-84 2>/dev/null

# 5. Avvia
/www/server/php/84/sbin/php-fpm --daemonize
sleep 2

# 6. Verifica
ps aux | grep php-fpm | grep -v grep

---

## 🖼️ Immagini non visibili (404)

Sintomo: le immagini del sito danno 404.

Cause possibili:
1. Symlink public/storage mancante o rotto
2. Permessi sbagliati su storage/app/public
3. Middleware/URL sbagliato

Fix:

cd /www/wwwroot/themedge.com/api

# 1. Ricrea symlink
rm -f public/storage
php artisan storage:link

# 2. Fix permessi
chown -R www:www storage/app/public
chmod -R 775 storage/app/public

# 3. Verifica
ls -la public/storage
# Atteso: public/storage -> storage/app/public

# 4. Test
curl -sI https://admin.themedge.com/storage/articles/featured/test.jpg | head -3

---

## 🔀 Redirect non funziona

Sintomo: crei un redirect ma il vecchio URL dà ancora 404.

Cause possibili:
1. Middleware HandleRedirects non registrato
2. Redirect disattivato (is_active = false)
3. Path non corrispondente (es. con/senza slash iniziale)

Fix:

cd /www/wwwroot/themedge.com/api

# 1. Verifica middleware registrato
grep -n "HandleRedirects" bootstrap/app.php
# Atteso: riga con $middleware->append(...)

# 2. Verifica redirect nel DB
php artisan tinker --execute="
\$r = App\Models\Redirect::where('from', 'like', '%tuo-path%')->first();
if (\$r) {
    echo 'From: ' . \$r->from . PHP_EOL;
    echo 'To: ' . \$r->to . PHP_EOL;
    echo 'Active: ' . (\$r->is_active ? 'YES' : 'NO') . PHP_EOL;
    echo 'Hits: ' . \$r->hits . PHP_EOL;
}
"

# 3. Pulisci cache
php artisan optimize:clear
/root/fpm.sh

Nota: il middleware agisce solo se la risposta è 404. Se il frontend Next.js risponde 200, il redirect non parte.

---

## 📝 Editor SEO non si aggiorna

Sintomo: cambi il titolo ma l'anteprima Google non si aggiorna.

Causa: i campi non hanno ->live(onBlur: true).

Fix:

cd /www/wwwroot/themedge.com/api

# Verifica nel file
grep -n "live(onBlur" app/Filament/Resources/ArticleResource.php

I campi title, slug, excerpt, content, meta_title, meta_description, featured_image devono avere ->live() o ->live(onBlur: true).

---

## 🔍 Ricerca Meilisearch non funziona

Sintomo: la ricerca restituisce 0 risultati.

Fix:

cd /www/wwwroot/themedge.com/api

# 1. Verifica Meilisearch attivo
systemctl status meilisearch
curl -s http://127.0.0.1:7700/health

# 2. Reimporta indice
php artisan scout:flush "App\Models\Article"
php artisan scout:import "App\Models\Article"

---

## 🎨 Filament 500 dopo modifica

Sintomo: dopo aver modificato un file Resource, la pagina dà errore 500.

Causa: OPcache non ricarica i file PHP.

Fix:

cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan view:clear
php artisan view:cache
/root/fpm.sh

---

## 📊 Audit Log non registra

Sintomo: le modifiche agli articoli non appaiono in /admin/activities.

Causa: namespace sbagliato (v4 invece di v5).

Fix — verifica namespace corretti:

cd /www/wwwroot/themedge.com/api
grep -n "use Spatie" app/Models/Article.php

Namespace v5 corretto:
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

Namespace v4 (sbagliato, non usare):
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

Anche il metodo dontSubmitEmptyLogs() è stato rinominato in v5:
→ dontLogEmptyChanges()

---

## 🔐 Login admin non funziona

Verifica:

cd /www/wwwroot/themedge.com/api
php artisan tinker --execute="
\$u = App\Models\User::where('email', 'admin@themedge.com')->first();
echo 'Email: ' . \$u->email . PHP_EOL;
echo 'Active: ' . (\$u->is_active ? 'YES' : 'NO') . PHP_EOL;
echo 'Roles: ' . \$u->getRoleNames()->implode(', ') . PHP_EOL;
"

Se is_active = false:

php artisan tinker --execute="
App\Models\User::where('email', 'admin@themedge.com')->update(['is_active' => true]);
"

Reset password:

php artisan tinker --execute="
\$u = App\Models\User::where('email', 'admin@themedge.com')->first();
\$u->update(['password' => bcrypt('NuovaPassword123!')]);
echo 'Password aggiornata';
"

---

## ⏱️ Sito lento

Cause possibili:
1. PHP-FPM pool saturo
2. MySQL query lente
3. OPcache disabilitato
4. Cache Laravel non attiva

Fix:

# Cache Laravel
cd /www/wwwroot/themedge.com/api
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verifica carico
free -h
df -h
top

# Verifica PHP-FPM
ps aux | grep php-fpm | wc -l

---

## 🚨 Emergenza: tutto down

Procedura di recovery rapida:

# 1. PHP-FPM
pkill -9 -f "php-fpm: master"
pkill -9 -f "php-fpm: pool"
rm -f /tmp/php-cgi-84.sock
/www/server/php/84/sbin/php-fpm --daemonize

# 2. Nginx
systemctl restart nginx

# 3. MySQL
systemctl restart mysql

# 4. Redis
systemctl restart redis

# 5. PM2 (frontend)
pm2 restart themedge-web

# 6. Test
curl -sI https://admin.themedge.com/admin/login | head -3
curl -sI https://themedge.com | head -3
curl -sI https://api.themedge.com/api/articles | head -3

---

## 📞 Debug avanzato

Log da controllare:

# Laravel
tail -100 /www/wwwroot/themedge.com/api/storage/logs/laravel.log

# Nginx admin
tail -50 /www/wwwlogs/admin.themedge.com.error.log

# Nginx site
tail -50 /www/wwwlogs/themedge.com.error.log

# PHP-FPM
tail -50 /www/server/php/84/var/log/php-fpm.log

# PM2 frontend
pm2 logs themedge-web --lines 50

Test connessioni:

# Database
mysql -uthemedge_user -p$(cat /root/.laravel_db_password) themedge_db -e "SELECT 1;"

# Redis
redis-cli -a $(grep requirepass /www/server/redis/redis.conf | cut -d' ' -f2) ping

# Meilisearch
curl -s http://127.0.0.1:7700/health

# PHP-FPM
ls -la /tmp/php-cgi-84.sock

---

## 💡 Best practice

1. Dopo ogni modifica PHP in produzione: /root/fpm.sh
2. Prima di modifiche rischiose: backup (/root/backup-themedge.sh)
3. Test sempre con curl prima di dire "tutto ok"
4. Log prima, fix dopo: leggi sempre i log prima di agire
5. Non usare systemctl con PHP-FPM
