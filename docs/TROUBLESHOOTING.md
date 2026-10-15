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

---

## 🔁 Loop GenerateArticleAudio (job TTS infiniti)

### Sintomo

- `php artisan queue:monitor default` mostra **migliaia** di job pending
- `storage/logs/laravel.log` cresce di **decine di MB** in pochi minuti
- `failed_jobs` cresce con lo stesso errore ripetuto
- Il worker `themedge-queue` è `online` ma processa solo job che falliscono
- CPU e I/O aumentano senza traffico utente

### Causa

`articles.audio_status` con default `pending` era ambiguo (sia "mai generato" che
"job in coda"). L'observer dispatchava un nuovo job ad ogni `save()` perché lo
stato restava invariato dopo il fallimento → loop infinito.

### Fix (commit `f8542ec`)

1. **Migration** `2026_10_14_181234_change_audio_status_default_in_articles`
2. **`ArticleObserver`**: dispatcha solo se `audio_status` è `''`/null, poi
   marca subito `pending` con `updateQuietly()`
3. **`GenerateArticleAudio`**: articoli corti → `skipped` + `$this->fail()` (no retry)

### Recovery (se il loop si ripresenta)

1. Fermare il worker: `pm2 stop themedge-queue`
2. Svuotare la coda (dopo eventuale dump):
   `mysql -D themedge_db -e "TRUNCATE TABLE jobs"`
   `mysql -D themedge_db -e "TRUNCATE TABLE failed_jobs"`
3. Clear cache + restart:
   `php artisan optimize:clear`
   `/root/fpm.sh`
   `pm2 restart themedge-queue`
4. Verificare: `php artisan queue:monitor default --max=100`

### Prevenzione

- **Non troncare mai un log durante un incidente** — copialo prima (vedi
  `HANDOFF.md` regola #14)
- Controllare `queue:monitor` periodicamente
- La `queue` è un **sistema vivo**: non ignorarla
- `logrotate` è già configurato per `laravel.log`
- Attenzione agli stati ambigui nel DB: un default non dovrebbe mai significare
  due cose diverse

---

## 🔁 Loop GenerateArticleAudio (job TTS infiniti)

Vedi sezione già documentata.

---

## 🧠 Ollama — token repeat limit reached

### Sintomo

- Job wiki/entità falliscono con: `Ollama API error: prediction aborted, token repeat limit reached`
- Output vuoto o troncato (`new_len: 63, old_len: 1378`)
- Wiki generate con contenuto di 0 char
- Log: `Ollama vuoto per wiki {"entity_id": X}`

### Causa

Con prompt lunghi (Wikipedia RAG + articoli), il modello `qwen2.5:3b` entra in loop di ripetizione:
- temperature troppo bassa (0.3)
- nessun `repeat_penalty`
- `num_ctx` default (4096) troppo piccolo per prompt Wikipedia

### Fix

In `OllamaClient::generate()`:

    'options' => array_merge([
        'temperature' => 0.6,        // da 0.3
        'top_p' => 0.9,
        'top_k' => 40,
        'repeat_penalty' => 1.15,
        'repeat_last_n' => 128,
        'num_predict' => 2500,       // da 2048
        'num_ctx' => 8192,           // da 4096 default
    ], $options),

### Verifica

Dopo il fix, una wiki generation non deve più produrre errori. Test:

    cat > /tmp/test_ollama.php << 'PHPEOF'
    <?php
    require '/www/wwwroot/themedge.com/api/vendor/autoload.php';
    $app = require_once '/www/wwwroot/themedge.com/api/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    use App\Models\Entity;
    use App\Services\Wiki\WikiGenerator;

    $e = Entity::find(23);  // Napoli
    $g = app(WikiGenerator::class);
    $r = $g->generateFromEntity($e, force: true);
    echo "OK: " . strlen($r->content ?? '') . " char\n";
    PHPEOF
    php /tmp/test_ollama.php

---

## 📏 Chunk Wikipedia troppo lungo (embedding error)

### Sintomo

- Log: `Ollama embedding error: input length exceeds the context length`
- Job `FetchWikipediaArticle` falliscono
- Chunk con lunghezza > 8000 char in tabella

### Causa

Il chunking di `WikipediaFetcherService::chunkAndEmbed()` non spezza i **singoli paragrafi** > 2000 char. Un paragrafo di 12000 char diventa un chunk da 12000 char.

### Fix

In `WikipediaFetcherService::chunkAndEmbed()`, aggiungi il controllo prima del `foreach`:

    if (mb_strlen($p) > $maxChars) {
        if (!empty(trim($current))) {
            $chunks[] = trim($current);
            $current = '';
        }
        $subChunks = mb_str_split($p, $maxChars - 200);
        foreach ($subChunks as $sub) {
            $sub = trim($sub);
            if (mb_strlen($sub) >= 100) {
                $chunks[] = $sub;
            }
        }
        continue;
    }

E in `OllamaEmbeddingService::embed()`:

    $text = mb_substr($text, 0, 6000);  // da 8000

### Verifica

    mysql -D themedge_db -e "
    SELECT MAX(CHAR_LENGTH(content)) AS max_chunk
    FROM wikipedia_chunks
    "

Atteso: `max_chunk < 3000`.

### Recovery

Se ci sono chunk troppo lunghi:

    cat > /tmp/rechunk.php << 'PHPEOF'
    <?php
    require '/www/wwwroot/themedge.com/api/vendor/autoload.php';
    $app = require_once '/www/wwwroot/themedge.com/api/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    use App\Models\Entity;
    use App\Services\Wiki\WikipediaFetcherService;

    $fetcher = app(WikipediaFetcherService::class);
    $ref = new ReflectionClass($fetcher);
    $method = $ref->getMethod('chunkAndEmbed');
    $method->setAccessible(true);

    foreach (Entity::where('wikipedia_status', 'ready')->get() as $e) {
        $n = $method->invoke($fetcher, $e);
        echo "{$e->name}: $n chunk\n";
    }
    PHPEOF
    php /tmp/rechunk.php

---

## ⏱️ Timeout DetectArticleEntities

### Sintomo

- Job `DetectArticleEntities` dopo 10 min: `FAIL`
- Nessuna entità creata
- Log: `No entities detected after all models`

### Causa

1. `llama3.1:8b` come primo modello → 5-10 min per risposta
2. `OllamaClient::$timeout = 600s` → timeout prima di finire
3. Non arriva mai al fallback `qwen2.5:3b`

### Fix

In `DetectArticleEntities`:

    public int $tries = 1;
    public int $timeout = 1200;  // da 600

    // Solo qwen2.5:3b (rimosso llama3.1:8b)
    $models = ['qwen2.5:3b'];

### Verifica

Test sync:

    php /tmp/test_detect.php  # 90-150s per 10 entità

---

## 🔗 Link entità → /entita/ invece di /wiki/

### Sintomo

- Su un articolo, clicchi "Giorgia Meloni" → vai a `/entita/giorgia-meloni` (pagina vuota)
- La wiki `/wiki/giorgia-meloni` esiste ma non è linkata

### Causa

1. `Entity::getUrlAttribute()` ritorna sempre `/entita/slug`
2. L'API non espone `wiki_url`
3. Il frontend usa `entity.url` che è sempre `/entita/`

### Fix

**1. In `Entity`**:

    public function wikiPage(): HasOne
    {
        return $this->hasOne(WikiPage::class)
            ->where('status', 'published')
            ->latest();
    }

    public function getUrlAttribute(): string
    {
        $wiki = $this->relationLoaded('wikiPage')
            ? $this->wikiPage
            : $this->wikiPage()->first();

        return $wiki ? '/wiki/' . $wiki->slug : '/entita/' . $this->slug;
    }

**2. In `ArticleController::formatArticle()`**:

    'entities' => $article->entities()->published()->ordered()->get()->map(fn ($e) => [
        'id' => $e->id,
        'name' => $e->name,
        'url' => $e->url,
        'wiki_url' => $e->wiki_url,  // ← nuovo
        // ...
    ]),

### Verifica

    curl -s "https://api.themedge.com/api/articles/meloni-svolta-su-russia-italia-politica" | python3 -c "
    import json, sys
    d = json.load(sys.stdin)
    for e in d.get('data', {}).get('entities', []):
        print(f\"{e.get('name')}: {e.get('url')}\")
    "

Atteso: `url=/wiki/slug` per tutte le entità con wiki.

---

## 📄 Paginazione /wiki rotta

### Sintomo

- Clicchi "2" su `/wiki` → URL cambia in `?page=2` ma il contenuto resta identico
- L'API risponde correttamente con `current_page: 2`

### Causa

`WikiHomePage` non leggeva `searchParams.page` → l'API riceveva sempre `page=1`.

### Fix

In `app/wiki/page.tsx`:

    type Props = {
      searchParams: Promise<{ page?: string; type?: string }>;
    };

    export default async function WikiHomePage({ searchParams }: Props) {
      const params = await searchParams;
      const currentPage = Math.max(1, parseInt(params.page ?? '1', 10) || 1);

      const [pagesData, featured, stats] = await Promise.all([
        getWikiPages({ per_page: 24, page: currentPage }),
        getFeaturedWiki(),
        getWikiStats(),
      ]);
      // ...
    }

E aggiungere pulsanti `← Prec` / `Succ →`.

### Verifica

    curl -s "https://themedge.com/wiki?page=1" | grep -oE 'href="/wiki/[^"?]*"' | grep -vE "grafo|categoria" | head -3
    curl -s "https://themedge.com/wiki?page=2" | grep -oE 'href="/wiki/[^"?]*"' | grep -vE "grafo|categoria" | head -3

Atteso: prime 3 voci **diverse** tra pagina 1 e 2.

---

## 🔄 Wiki torna a draft dopo rigenerazione

### Sintomo

- Wiki pubblicata → click "Rigenera AI" → wiki sparisce dal sito (404)
- `status` cambia da `published` a `draft`

### Causa

`WikiGenerator::generateFromEntity()` includeva `'status' => 'draft'` nel blocco `$data` usato sia per `create` che per `update`. Su `update`, sovrascriveva `status`.

### Fix

In `WikiGenerator::generateFromEntity()`:

    // RIMUOVI 'status' => 'draft' dal blocco $data

    if ($existing) {
        $existing->update($data);
        $page = $existing;
    } else {
        $data['status'] = 'draft';  // SOLO per nuove pagine
        $page = WikiPage::create($data);
    }

### Verifica

Rigenera una wiki pubblicata:

    mysql -D themedge_db -e "
    SELECT id, title, status FROM wiki_pages WHERE id = 2
    "
    # Deve restare 'published'

---

## 🔗 Vedi anche

- `HANDOFF.md` — Regole #15-19
- `docs/WIKI.md` — Wikipedia RAG + Image + Entity
- `docs/RAG.md` — Wikipedia RAG
- `docs/OLLAMA.md` — Configurazione Ollama
- `CHANGELOG.md` — Sezione [2026-10-15i]
