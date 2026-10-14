# 🔧 Troubleshooting — Themedge

Problemi comuni e soluzioni.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [React #418 — Hydration Mismatch](#react-418--hydration-mismatch)
2. [Next.js — Errori build](#nextjs--errori-build)
3. [Laravel — Errori comuni](#laravel--errori-comuni)
4. [Ollama — Problemi RAM e lentezza](#ollama--problemi-ram-e-lentezza)
5. [PHP-FPM — Crash loop](#php-fpm--crash-loop)
6. [Permessi file](#permessi-file)
7. [Cron — Sovrascrittura accidentale](#cron--sovrascrittura-accidentale)
8. [Migration — Timestamp duplicati](#migration--timestamp-duplicati)
9. [Path duplicati con cp -r](#path-duplicati-con-cp--r)
10. [Terminale SSH bloccato](#terminale-ssh-bloccato)
11. [Embed — Problemi](#embed--problemi)
12. [Cloudflare — Cache stantia](#cloudflare--cache-stantia)
13. [Filament — Errori admin](#filament--errori-admin)
14. [Database — Problemi](#database--problemi)

---

## React #418 — Hydration Mismatch

### Sintomo

```
Uncaught Error: Minified React error #418;
Hydration failed because the server rendered text didn't match the client.
```

In console browser (solo in produzione, minified).

### Cause comuni

| Causa | Esempio |
|-------|---------|
| `typeof window` | `const isMobile = typeof window !== 'undefined'` |
| `navigator.*` | `const canShare = 'share' in navigator` |
| `localStorage` | `const token = localStorage.getItem('x')` |
| `Date.now()` | `const now = Date.now()` |
| `Math.random()` | `const id = Math.random()` |
| Date locale | `new Date().toLocaleDateString('it-IT')` |
| `next/image` srcset | Immagine con `sizes` dinamico |

### Fix — Pattern SSR-safe

```tsx
const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);

// Usa mounted PRIMA di qualsiasi accesso browser-only
const value = mounted && typeof window !== 'undefined' ? ... : fallback;
```

### Fix — Date

```tsx
<time suppressHydrationWarning>
  {new Date(article.published_at).toLocaleDateString('it-IT')}
</time>
```

### Debug

**Build in dev mode** (NON su produzione):

```bash
cd /www/wwwroot/themedge.com/web
NODE_ENV=development npm run build
# Mostra errori non minified con componente esatto
```

---

## Next.js — Errori build

### "Could not find BUILD_ID"

**Causa:** build incompleta o `.next` corrotta.

**Fix:**

```bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
```

### "Module not found"

**Causa:** dipendenza mancante o path errato.

**Fix:**

```bash
# Verifica import
npx tsc --noEmit --project tsconfig.json 2>&1 | head -20

# Reinstalla node_modules
rm -rf node_modules package-lock.json
npm install
npm run build
```

### "Error: Turbopack build failed"

**Causa:** errore sintassi TypeScript/JSX.

**Fix:**

```bash
# TypeScript check
npx tsc --noEmit --project tsconfig.json 2>&1 | head -30

# Guarda l'errore esatto
npm run build 2>&1 | tail -30
```

### 500 su una pagina specifica

**Causa:** errore server-side in Server Component.

**Fix:**

```bash
pm2 logs themedge-web --lines 50 --nostream
```

### Next.js non risponde (502 Nginx)

**Causa:** Next.js crashato o porte occupate.

**Fix:**

```bash
pm2 list
pm2 restart themedge-web --update-env
sleep 3
curl -I http://127.0.0.1:3000/
```

---

## Laravel — Errori comuni

### "Storage path not writable"

**Fix:**

```bash
chown -R www:www /www/wwwroot/themedge.com/api/storage
chmod -R 775 /www/wwwroot/themedge.com/api/storage
```

### "Class not found"

**Fix:**

```bash
cd /www/wwwroot/themedge.com/api
composer dump-autoload
php artisan optimize:clear
```

### "Config cache stale"

**Fix:**

```bash
cd /www/wwwroot/themedge.com/api
php artisan config:clear
php artisan optimize:clear
```

### "SQLSTATE: Base table or view not found"

**Causa:** migration non eseguita o tabella cancellata.

**Fix:**

```bash
php artisan migrate:status
php artisan migrate --force
```

### "Too many connections"

**Causa:** troppe richieste PHP-FPM.

**Fix:**

```bash
# Riavvia PHP-FPM
[FPM_SCRIPT]

# Verifica processi
ps aux | grep php-fpm | wc -l
```

### "View cache stale"

**Fix:**

```bash
php artisan view:clear
php artisan optimize:clear
```

---

## Ollama — Problemi RAM e lentezza

### Sintomo: risposta dopo 2+ minuti

**Causa:** modello troppo grande per la RAM disponibile → swap.

**Diagnosi:**

```bash
free -h
ollama ps
```

Se `Mem: used > 80%` o `Swap: used > 1 GB` → problema RAM.

### Fix — Ferma modelli inutilizzati

```bash
ollama stop llama3.1:8b
ollama stop qwen2.5:3b
sleep 2
free -h
```

### Fix — Usa modello più piccolo

```bash
cd /www/wwwroot/themedge.com/api

# Backup
cp .env ".env.bak-$(date +%s)"

# Modifica modello RAG
sed -i 's/OLLAMA_MODEL=llama3.1:8b/OLLAMA_MODEL=qwen2.5:3b/' .env
sed -i 's/OLLAMA_MODEL_EXTRACTION=qwen2.5:3b/OLLAMA_MODEL_EXTRACTION=qwen2.5:3b/' .env

php artisan config:clear
```

### Fix — Forza kill

```bash
pkill -9 -f "llama-server"
sleep 3
systemctl restart ollama
curl http://localhost:11434/api/tags
```

### Sintomo: Ollama non risponde

```bash
# Verifica servizio
systemctl status ollama

# Restart
systemctl restart ollama

# Test
curl http://localhost:11434/api/tags
```

### Sintomo: "connection refused"

```bash
# Verifica porta
ss -tlnp | grep 11434

# Riavvia
systemctl restart ollama
```

### Prevenzione — Cron cleanup

```bash
# Ferma modelli inattivi ogni 30 min
*/30 * * * * ollama ps | grep -E "Minutes|Hours" | awk '{print $1}' | xargs -I{} ollama stop {} 2>/dev/null
```

---

## PHP-FPM — Crash loop

### Sintomo

```
502 Bad Gateway
php-fpm-84.service: Start request repeated too quickly
```

### Causa

`systemctl restart php-fpm-84` → servizio masked → crash loop.

### Fix

**MAI usare `systemctl`.** Usa `[FPM_SCRIPT]`.

**Se già in crash loop:**

```bash
# 1. Stop
/etc/init.d/php-fpm-84 stop

# 2. Kill
pkill -9 -f php-fpm

# 3. Pulisci socket
rm -f /tmp/php-cgi-84.sock

# 4. Avvia manualmente
/www/server/php/84/sbin/php-fpm --daemonize

# 5. Verifica
ps aux | grep php-fpm | head -5
```

### Prevenzione

Usa **SEMPRE** `[FPM_SCRIPT]` per riavviare.

---

## Permessi file

### Sintomo

```
RuntimeException: Could not open input file
File not writable
```

### Diagnosi

```bash
find /www/wwwroot/themedge.com/api -not -user www 2>/dev/null | wc -l
find /www/wwwroot/themedge.com/web -not -user www 2>/dev/null | wc -l
```

### Fix

```bash
# Owner
chown -R www:www /www/wwwroot/themedge.com/api
chown -R www:www /www/wwwroot/themedge.com/web

# Permessi
find /www/wwwroot/themedge.com/api -type d -exec chmod 755 {} \;
find /www/wwwroot/themedge.com/api -type f -exec chmod 644 {} \;

# Storage + cache (scrive)
chmod -R 775 /www/wwwroot/themedge.com/api/storage
chmod -R 775 /www/wwwroot/themedge.com/api/bootstrap/cache

# .env
chmod 640 /www/wwwroot/themedge.com/api/.env
chmod 640 /www/wwwroot/themedge.com/web/.env.local
```

### Prevenzione

**SEMPRE** dopo `cat > file` o `cp` da root:

```bash
chown -R www:www /percorso
```

---

## Cron — Sovrascrittura accidentale

### Sintomo

Cron spariti.

### Causa

```bash
echo "..." | crontab -   # ❌ SOVRASCRIVE TUTTO
```

### Fix — Ripristino

```bash
crontab -e
```

**Aggiungi:**

```bash
# Backup giornaliero
0 3 * * * /root/backup-themedge.sh > /dev/null 2>&1

# Cleanup log
0 */6 * * * /root/cleanup-logs.sh > /dev/null 2>&1

# Check disk
0 * * * * /root/check-disk.sh > /dev/null 2>&1

# Cleanup Ollama
*/30 * * * * ollama ps | grep -E "Minutes|Hours" | awk '{print $1}' | xargs -I{} ollama stop {} 2>/dev/null

# Fix permessi audio
*/5 * * * * chown -R www:www /www/wwwroot/themedge.com/api/storage/app/public/audio/ > /dev/null 2>&1

# Pulizia query RAG vecchie
0 4 * * 0 cd /www/wwwroot/themedge.com/api && php artisan tinker --execute="App\Models\AskQuery::where('created_at', '<', now()->subDays(90))->delete();" > /dev/null 2>&1
```

### Prevenzione

**SEMPRE** prima di modificare cron:

```bash
crontab -l > /root/crontab.backup.$(date +%s)
```

---

## Migration — Timestamp duplicati

### Sintomo

```
SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table
```

### Causa

Due migration con **stesso timestamp** → Laravel le ordina alfabeticamente.

### Fix

```bash
cd /www/wwwroot/themedge.com/api

# 1. Rimuovi le migration problematiche
rm -f database/migrations/2026_XX_XX_HHMMSS_*.php

# 2. Drop tabelle se create
php artisan tinker
# In tinker:
# Schema::dropIfExists('nome_tabella');

# 3. Rimuovi entry migration
php artisan tinker
# In tinker:
# DB::table('migrations')->where('migration', 'like', '%nome%')->delete();

# 4. Ricrea con timestamp progressivi
# usa 2026_10_14_120000, 2026_10_14_120001, ecc.

# 5. Migrate
php artisan migrate --force
```

### Prevenzione

**Timestamp progressivi:**

```bash
TS1="2026_10_14_120000"
TS2="2026_10_14_120001"
```

**Oppure `sleep 1` tra due `date +%Y_%m_%d_%H%M%S`.**

---

## Path duplicati con `cp -r`

### Sintomo

```
create mode 100644 web/components/ask/ask/AskChat.tsx
```

### Causa

```bash
cp -r src/ dest/   # se dest/src/ esiste → dest/src/src/
```

### Fix

```bash
cd /www/wwwroot/themedge-repo

# Verifica
find web/components/ask -type d

# Rimuovi duplicato
rm -rf web/components/ask/ask
rm -rf web/app/wiki/wiki

# Git cleanup
git rm -r --cached web/components/ask/ask 2>/dev/null
git rm -r --cached web/app/wiki/wiki 2>/dev/null

git add .
git commit -m "chore: rimozione path duplicati"
git push origin main
```

### Prevenzione

**Prima di `cp -r`**, verifica:

```bash
ls -la src/ dest/
```

**Oppure usa `rsync`** (gestisce meglio):

```bash
rsync -av src/ dest/
```

---

## Terminale SSH bloccato

### Sintomo 1: `>` prompt

**Causa:** heredoc in attesa di chiusura.

**Fix:** premi **`Ctrl+C`** sulla tastiera (non scrivere "Ctrl+C").

### Sintomo 2: non copia più

**Causa:** `php artisan tinker --execute="...\x27..."` ha lasciato la shell in stato quote.

**Fix:** premi **`Ctrl+C`** + **`Enter`**.

### Sintomo 3: comandi non trovati

**Causa:** hai incollato **markdown** nel terminale invece di comandi bash.

**Fix:** pulisci con `clear`, poi incolla solo **comandi**, non testo formattato.

### Prevenzione

**MAI** `tinker --execute` con `\x27`. Usa **file PHP temporanei**:

```bash
cat > /tmp/script.php << 'PHPEOF'
<?php
require ...;
$app = require ...;
$app->make(Kernel::class)->bootstrap();
// codice
PHPEOF
php /tmp/script.php
```

---

## Embed — Problemi

### Iframe non appare

**Cause:**

1. HTML sanitizzato troppo aggressivo
2. Iframe dentro `<p>` (HTML non valido)
3. `sanitizeHtml` rimuove iframe

**Fix:**

- Verifica `sanitize.ts` in frontend abbia `iframe` in `ALLOWED_TAGS`
- Verifica `autoEmbedUrls` chiuda `<p>` prima dell'iframe
- Verifica il content nel DB contenga `<iframe>`

```bash
cd /www/wwwroot/themedge.com/api

cat > /tmp/check_content.php << 'PHPEOF'
<?php
require __DIR__ . '/../../www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once __DIR__ . '/../../www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$a = App\Models\Article::find(1);
echo $a->content;
PHPEOF

php /tmp/check_content.php
```

### Video non parte al click

**Causa:** `VideoEmbed` nasconde iframe fino al click.

**Fix:** verifica che il bottone abbia `onClick={() => setLoaded(true)}`.

### iframe rimosso da DOMPurify

**Fix:** aggiungi `iframe` a `ALLOWED_TAGS` in `lib/sanitize.ts`:

```ts
const ALLOWED_TAGS = [
  'p', 'br', ...,
  'iframe', 'video', 'audio',
];
```

---

## Cloudflare — Cache stantia

### Sintomo

Modifiche non visibili.

### Fix — Purge

```bash
cd /www/wwwroot/themedge.com/api

ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)

curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}' \
  -w "\nHTTP: %{http_code}\n"
```

### Fix — Bypassa cache in test

```bash
curl -s "https://themedge.com/articolo/slug?nocache=$(date +%s)" | grep -i "..."
```

### Fix — Verifica stato

```bash
curl -sI https://themedge.com/articolo/slug | grep -iE "cf-cache-status|age"
```

---

## Filament — Errori admin

### 500 su una Resource

**Causa:** errore in `infolist`, `form`, o `table`.

**Debug:**

```bash
tail -50 /www/wwwroot/themedge.com/api/storage/logs/laravel.log
```

**Fix comune:** rimuovi `ViewAction` se l'infolist causa timeout:

```php
Tables\Actions\ViewAction::make(),  // rimuovi questa
```

### Colonne non visibili

**Fix:** verifica `->visible()` o `->hidden()`.

### Azioni non funzionanti

**Fix:** verifica permessi Shield:

```bash
php artisan shield:generate --all
```

### "Column not found"

**Fix:** esegui migration + clear cache:

```bash
cd /www/wwwroot/themedge.com/api
php artisan migrate --force
php artisan optimize:clear
php artisan filament:optimize-clear
```

---

## Database — Problemi

### "Cannot truncate a table referenced in a foreign key constraint"

**Fix:**

```php
DB::statement('SET FOREIGN_KEY_CHECKS=0');
AskFeedback::truncate();
AskQuery::truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1');
```

### "Duplicate entry for key"

**Fix:** usa `updateOrCreate` invece di `create`.

### Query lente

**Fix:** aggiungi indici:

```php
$table->index('column_name');
```

### Connessione rifiutata

```bash
# Verifica MySQL
systemctl status mysql
mysql -u root -p -e "SELECT 1;"
```

---

## Script di emergenza — Quick fix

**Se tutto è rotto, esegui in ordine:**

```bash
# 1. Fix permessi
chown -R www:www /www/wwwroot/themedge.com/api
chown -R www:www /www/wwwroot/themedge.com/web

# 2. PHP-FPM
[FPM_SCRIPT]

# 3. Cache Laravel
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

# 4. Next.js rebuild
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env

# 5. Queue worker
pm2 restart themedge-queue

# 6. Purge Cloudflare
cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

# 7. Test
curl -I https://themedge.com
pm2 list
```

---

*Ultima modifica: 14 ottobre 2026*
