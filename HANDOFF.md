# 🤝 HANDOFF — Themedge

**Repo:** https://github.com/Serialc0de/themedge
**Ultimo commit:** 812129a (feat(entity): URL preferisce wiki se esiste)
**Sito:** https://themedge.com
**Admin:** https://admin.themedge.com/admin
**VPS:** 178.83.121.96 (Ubuntu 26.04, 4 CPU, 7.75 GB RAM, 38 GB disco)
**Stato progetto:** ~99% completo

---

## 📋 ISTRUZIONI PER IL NUOVO OPERATORE

Leggi TUTTO questo documento prima di fare qualsiasi cosa.

Poi:
1. Esegui i check iniziali (sezione in fondo)
2. Incollami output + obiettivo specifico
3. Aspetta istruzioni prima di modificare

---

## 🚨 REGOLE CRITICHE — MAI VIOLARE

### 1. PHP-FPM — MAI systemctl

Il servizio php-fpm-84.service è masked in systemd. systemctl restart causa crash loop.

SEMPRE: /root/fpm.sh (script dedicato)

### 2. OPcache validate_timestamps = Off

Dopo ogni modifica PHP → eseguire /root/fpm.sh.

### 3. Next.js — SEMPRE npm run build prima di pm2 restart

❌ MAI:
pm2 restart themedge-web

✅ SEMPRE:
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
pm2 stop themedge-web
rm -rf .next
npm run build
chown -R www:www .next
pm2 restart themedge-web --update-env

Motivo: Next.js perde BUILD_ID → crash loop. Il chown è obbligatorio perché npm run build gira come root e PM2 serve come www.

Script /root/build-web.sh disponibile per fare tutto in un comando.

### 4. pm2 stop PRIMA della build critica

Vedi regola #3 (già incluso).

### 5. Modifica in PRODUZIONE, sync nel repo

MAI modificare direttamente /www/wwwroot/themedge-repo/.

SEMPRE:
1. Modifica in /www/wwwroot/themedge.com/
2. rsync verso /www/wwwroot/themedge-repo/
3. git commit + git push

### 6. Timeout Ollama

OLLAMA_TIMEOUT=600
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_EMBEDDING_TIMEOUT=60

⚠️ RAM: llama3.1:8b (4.9 GB) non usare per RAG. Usare qwen2.5:3b (1.9 GB).

### 7. Queue worker

Il worker PM2 themedge-queue gira in background.

- ❌ MAI riavviarlo durante un job AI
- ✅ Dopo modifiche → pm2 restart themedge-queue

⚠️ Sintomi di loop: se failed_jobs cresce rapidamente o il log cresce di decine di MB in pochi minuti, fermare subito il worker (pm2 stop themedge-queue) e seguire docs/TROUBLESHOOTING.md → "Loop GenerateArticleAudio".

### 8. MAI php artisan tinker --execute con quote escape

Rompe il terminale SSH. Usa file PHP temporanei:

cat > /tmp/script.php << 'PHPEOF'
<?php
require '/www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once '/www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// codice
PHPEOF
php /tmp/script.php

### 9. MAI sed su JSX/TSX

Rompe la sintassi. Usa nano a mano o cat > file << 'EOF'.

### 10. MAI npm run dev in produzione

Scrive in .next/ e contamina la build.

### 11. cp -r può creare path duplicati

cp -r src/ dest/ se dest/src/ esiste → dest/src/src/. Verifica con find.

### 12. Timestamp migration diversi

Due migration con stesso secondo → Laravel le ordina alfabeticamente. Usa timestamp progressivi (sleep 1 tra due date).

### 13. Cron — MAI echo "..." | crontab -

Sovrascrive tutti i cron. Usa crontab -e.

Backup prima: crontab -l > /root/crontab.bak.$(date +%s)

### 14. Log — MAI troncare durante un incidente

Se il log cresce troppo:

❌ MAI durante un debug attivo:
> storage/logs/laravel.log

✅ SEMPRE:
cp storage/logs/laravel.log /root/incident-$(date +%s).log
> storage/logs/laravel.log

### 15. Ollama — num_ctx 8192 obbligatorio per RAG

Con prompt lunghi (Wikipedia RAG + chunk + articoli), il default num_ctx=4096 di qwen2.5:3b tronca il prompt e causa:
- Risposte vuote/troncate
- Errori "token repeat limit reached"
- Output degenerati

In OllamaClient::generate():

'options' => array_merge([
    'temperature' => 0.6,
    'top_p' => 0.9,
    'top_k' => 40,
    'repeat_penalty' => 1.15,
    'repeat_last_n' => 128,
    'num_predict' => 2500,
    'num_ctx' => 8192,      // OBBLIGATORIO
], $options),

### 16. Wikipedia chunking — max 2000 char per chunk

Il chunking Wikipedia DEVE spezzare i paragrafi > 2000 char.

Se un chunk supera 8000 char:
- Ollama embedding fallisce: "input length exceeds the context length"
- Job bloccati, log pieni, coda in stallo

WikipediaFetcherService::chunkAndEmbed() deve sempre spezzare:

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

Verifica: SELECT MAX(CHAR_LENGTH(content)) FROM wikipedia_chunks; → deve essere < 3000.

### 17. Non usare llama3.1:8b per RAG/estrazione

llama3.1:8b (4.9 GB) impiega 5-10 min per risposta con prompt lunghi. Su CPU:
- Timeout Ollama (600s) → job falliti
- RAM saturata (5.6 GB)
- Blocca la coda

Usare sempre qwen2.5:3b (1.9 GB) per:
- Estrazione entità
- Generazione wiki
- RAG chat
- Summary

llama3.1:8b solo per task offline e non bloccanti.

.env:
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b

### 18. Entity extraction — auto-create entità mancanti

DetectArticleEntities crea entità mancanti (is_published=false) e collega tutte all'articolo via syncWithoutDetaching.

Effetto: dopo "🔍 Rileva entità (AI)", l'articolo ha 5-15 entità collegate visibili in Admin → Entità come bozze da revisionare.

Pubblicare le entità manualmente per renderle visibili sul frontend.

### 19. Link entità → wiki preferito

Entity::getUrlAttribute() preferisce /wiki/slug se esiste una WikiPage pubblicata per l'entità, altrimenti /entita/slug.

Regola: se una entità ha wiki, il link punta alla wiki (con contenuto), non alla pagina /entita/ (vuota).

Verifica:
mysql -D themedge_db -e "
SELECT e.id, e.name, 
       (SELECT id FROM wiki_pages WHERE entity_id = e.id AND status = 'published' LIMIT 1) AS wiki_id
FROM entities e
WHERE e.id >= 24
"
Tutte le entity con wiki devono avere wiki_id NOT NULL.

---

## 📁 STRUTTURA PROGETTO

/www/wwwroot/themedge.com/
├── api/                            Laravel 13 + Filament 3
│   ├── app/
│   │   ├── Filament/
│   │   │   ├── Resources/          20 Resource
│   │   │   ├── Widgets/            16 Widget
│   │   │   └── Pages/
│   │   ├── Http/Controllers/Api/   15+ Controller
│   │   ├── Jobs/                   GenerateArticleAudio, GenerateWikiPageFromEntity, FetchWikiImage, FetchWikipediaArticle, DetectArticleEntities, GenerateArticleEmbedding, GenerateWikiEmbedding, GenerateArticleSummary, GenerateSeoSuggestions, DetectArticleClaims
│   │   ├── Livewire/
│   │   ├── Models/                 23+ modelli
│   │   ├── Observers/              ArticleObserver
│   │   ├── Console/Commands/       8+ comandi (ask:prune, wiki:fetch-images, wiki:fetch-wikipedia, wiki:generate, rag:embed-all, tts:test, seo:scan-links, analytics:snapshot)
│   │   └── Services/
│   │       ├── Llm/                OllamaClient, ClaimExtractor, EntityExtractor
│   │       ├── Wiki/               WikiGenerator, WikipediaFetcherService, WikipediaRagService
│   │       ├── Rag/                OllamaEmbedding, RagSearch, RagPromptBuilder
│   │       └── Tts/                PiperTtsService
│   ├── config/
│   │   ├── services.php
│   │   ├── tts.php
│   │   └── highlights.php
│   ├── database/migrations/        58+ migration
│   └── routes/api.php              70+ endpoint
├── web/                            Next.js 16
│   ├── app/
│   │   ├── wiki/                   Home, dettaglio, categoria, cerca, grafo
│   │   ├── chiedi/                 AI Chat RAG
│   │   ├── articolo/[slug]/
│   │   └── ...
│   ├── components/
│   │   ├── article/                AudioPlayerPro, Highlight*, ArticleEntities
│   │   ├── ask/                    AskChat, AskMessage, AskSources
│   │   ├── wiki/                   WikiCard, WikiImageAttribution, WikiInfobox, WikiGraph
│   │   └── ...
│   ├── lib/
│   │   ├── wiki.ts
│   │   ├── ask.ts
│   │   ├── audio-storage.ts
│   │   └── use-audio-player.ts
│   └── .env.local
└── storage/

---

## 🗄️ DATABASE — MODELLI

### Editoriale
- Article, User, Category, Tag, Comment, MediaAsset, Redirect

### Trust & Credibilità
- ArticleSource, Correction, ArticleFactCheck, ArticleClaim
- ArticleKnowledgeItem, ArticleTimelineEvent, GlossaryTerm
- Entity, ScrollySection

### Knowledge Engine
- WikiPage, WikiRevision, WikipediaChunk

### AI & RAG
- AskQuery, AskFeedback

### Campi aggiunti
- Article: audio_*, embedding, embedding_model, embedding_generated_at
- WikiPage: embedding, image_source_url, image_license, image_author, image_attribution, image_fetched_at, image_status
- Entity: wikipedia_url, wikipedia_text, wikipedia_lang, wikipedia_fetched_at, wikipedia_status

---

## 🤖 OLLAMA — CONFIGURAZIONE

### Modelli installati

| Modello | Dimensione | Uso |
|---------|-----------|-----|
| llama3.1:8b | 4.9 GB | Non usare per RAG |
| qwen2.5:3b | 1.9 GB | RAG + extraction (consigliato) |
| nomic-embed-text | 275 MB | Embedding RAG |
| qwen2:0.5b | 400 MB | Fallback veloce |

### Config .env

OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
OLLAMA_EMBEDDING_TIMEOUT=60
FACTCHECK_AI_ENABLED=true

### Kill switch

FACTCHECK_AI_ENABLED=false
php artisan config:clear

### Comandi utili

ollama list              # lista modelli
ollama ps                # modelli in RAM
ollama stop llama3.1:8b  # ferma modello

---

## 📋 COMANDI OPERATIVI QUOTIDIANI

### Riavvio completo

# PHP-FPM
/root/fpm.sh

# Next.js (con script dedicato)
/root/build-web.sh

# Queue worker
pm2 restart themedge-queue

# Cache Laravel
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

### Purge Cloudflare

cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}' \
  -w "\nHTTP: %{http_code}\n"

### Backup manuale

/root/backup-themedge.sh
ls -lh /root/backups/themedge/

### Comandi AI/Wiki/RAG

cd /www/wwwroot/themedge.com/api

# Fetch Wikipedia per entità
php artisan wiki:fetch-wikipedia --limit=50
php artisan wiki:fetch-wikipedia --entity=5 --sync
php artisan wiki:fetch-wikipedia --force --status=ready

# Fetch immagini wiki
php artisan wiki:fetch-images --limit=50
php artisan wiki:fetch-images --id=3 --sync --force

# Genera wiki da entità
php artisan wiki:generate --limit=10
php artisan wiki:generate --entity=24 --sync

# Genera embedding RAG
php artisan rag:embed-all --type=both --sync

# Pulizia query RAG vecchie
php artisan ask:prune --days=90

# Test TTS
php artisan tts:test "Ciao mondo" --show

---

## 🚨 COMANDI DI EMERGENZA

### Sito 500

pm2 list
/root/fpm.sh
systemctl status nginx --no-pager
tail -50 /www/wwwroot/themedge.com/api/storage/logs/laravel.log
pm2 logs themedge-web --lines 50 --nostream

cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

### Next.js non parte

cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
ls -la .next/BUILD_ID
# Se manca:
npm run build
chown -R www:www .next
pm2 restart themedge-web --update-env

### PHP-FPM crash loop

/etc/init.d/php-fpm-84 stop
pkill -9 -f php-fpm
rm -f /tmp/php-cgi-84.sock
/www/server/php/84/sbin/php-fpm --daemonize

### Queue worker fermo

pm2 list | grep themedge-queue
pm2 restart themedge-queue

### Queue in loop (job che falliscono)

pm2 stop themedge-queue
cd /www/wwwroot/themedge.com/api
php artisan queue:monitor default --max=100
mysql -D themedge_db -e "SELECT COUNT(*) FROM jobs"
mysql -D themedge_db -e "SELECT COUNT(*) FROM failed_jobs"
# Se migliaia → TRUNCATE (dopo dump)
mysql -D themedge_db -e "TRUNCATE TABLE jobs"
mysql -D themedge_db -e "TRUNCATE TABLE failed_jobs"
pm2 restart themedge-queue

### RAM saturata (Ollama)

ollama ps
ollama stop llama3.1:8b
ollama stop qwen2.5:3b
free -h

### Disco pieno

df -h /
find /www/wwwroot/themedge.com -name "*.bak*" -mtime +3 -delete
pm2 flush
> /www/wwwroot/themedge.com/api/storage/logs/laravel.log
journalctl --vacuum-time=7d

### React #418 in console

Pattern SSR-safe obbligatorio:

const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);

const value = mounted && typeof window !== 'undefined' ? ... : fallback;

### Errore permessi

chown -R www:www /www/wwwroot/themedge.com/api
chown -R www:www /www/wwwroot/themedge.com/web
chmod -R 775 /www/wwwroot/themedge.com/api/storage
chmod -R 775 /www/wwwroot/themedge.com/api/bootstrap/cache

---

## 📊 ROADMAP FUTURA

### Sessione 8 — Paywall / Stripe (6-8h)
- Stripe Checkout + Webhook
- Metered paywall (3 articoli gratis/mese)
- Flag articolo premium
- Area abbonati

### Sessione 9 — Newsletter + Email (5-6h)
- SMTP setup (Brevo/Mailgun)
- Notifiche commenti
- Newsletter editor
- Segmentazione lettori

### Sessione 10 — OAuth Google (3-4h)
- Google Cloud Console setup
- Login + migrazione token reader

### Sessione 11 — Rifiniture (5-10h)
- Versioning UI Wiki (diff visuale)
- SEO Wiki avanzato (sitemap, RSS)
- Cache Redis RAG
- Podcast RSS auto-generato

---

## 🏆 STATISTICHE PROGETTO

| Metrica | Valore |
|---------|--------|
| Ore di lavoro | ~90 |
| Giorni | 8 |
| Pagine frontend | 30+ |
| API endpoints | 70+ |
| Modelli Eloquent | 23+ |
| Filament Resources | 20 |
| Filament Widgets | 16 |
| Migration | 58+ |
| Componenti React | 85+ |
| Commit | ~80 |
| Feature AI | 10 |
| Wiki generate | 30 |
| Entity con Wikipedia | 21+ |
| Wikipedia chunk | 573+ |

---

## ✅ CHECK INIZIALE — COSA FARE ALL'INIZIO

Esegui questi comandi e incollami l'output:

# 1. Stato sistema
pm2 list
df -h /
free -h
ollama ps

# 2. Test sito
curl -I https://themedge.com
curl -I https://admin.themedge.com/admin
curl -s https://api.themedge.com/api/articles | python3 -m json.tool | head -5

# 3. Riavvio PHP-FPM (sempre)
/root/fpm.sh

# 4. Cache clear Laravel
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

# 5. Verifica log errori
tail -30 storage/logs/laravel.log | grep -i error
pm2 logs themedge-web --lines 20 --nostream

# 6. Verifica Git
cd /www/wwwroot/themedge-repo
git status --short
git log --oneline -5

# 7. Verifica cron
crontab -l

# 8. Verifica permessi
find /www/wwwroot/themedge.com/api -not -user www 2>/dev/null | wc -l
find /www/wwwroot/themedge.com/web -not -user www 2>/dev/null | wc -l

# 9. Verifica DB stato
mysql -D themedge_db -e "SELECT status, COUNT(*) FROM wiki_pages GROUP BY status"
mysql -D themedge_db -e "SELECT wikipedia_status, COUNT(*) FROM entities GROUP BY wikipedia_status"
mysql -D themedge_db -e "SELECT COUNT(*) FROM jobs"

Poi dimmi:
1. Output dei comandi
2. Il tuo obiettivo specifico per questa sessione
3. Aspetta istruzioni prima di modificare

---

## 🎁 BENVENUTO

Erediti un progetto ~99% completo con:

- 📰 Giornale online editoriale professionale
- 🧠 Knowledge Engine unico in Italia (Wiki + Graph + AI Chat + Wikipedia RAG)
- 🎙️ TTS Premium (Piper, gratis, offline)
- 🤖 AI locale (Ollama, zero costi)
- ⚡ Performance TTFB 30-80ms
- 📱 PWA + push notifications
- 🌐 Cloudflare CDN + WAF
- 📊 Analytics + feedback
- 🔒 GDPR compliance
- 🖼️ Wikipedia RAG (unico in Italia)
- 🏷️ Entity extraction automatica

Buon lavoro!

---

## 🔑 CREDENZIALI

File sul server (NON versionati):

- MySQL: /root/.my.cnf
- Redis: /www/server/redis/redis.conf
- Meilisearch: /root/.meilisearch_master_key
- GitHub token: /root/.github-token
- Laravel APP_KEY: /www/wwwroot/themedge.com/api/.env
- VAPID keys: /www/wwwroot/themedge.com/api/.env
- Cloudflare token: /www/wwwroot/themedge.com/api/.env
- Admin panel: admin@themedge.com

Utenti di test:
- editor@themedge.com → EditorPassword123!
- test.editor@themedge.com → AuthorPassword123!

NON committare MAI queste credenziali su GitHub.

---

## 📞 URL UTILI

- GitHub: https://github.com/Serialc0de/themedge
- Sito: https://themedge.com
- Admin: https://admin.themedge.com/admin
- API: https://api.themedge.com/api
- Cloudflare: https://dash.cloudflare.com

---

## 📚 Documentazione disponibile

Nel repo:

- HANDOFF.md — Panoramica progetto + regole
- docs/FEATURES.md — Tutte le feature
- docs/TROUBLESHOOTING.md — Problemi + fix
- docs/DEPLOY.md — Deploy + rollback
- docs/SETUP.md — Setup dev
- docs/RAG.md — Knowledge Engine AI
- docs/WIKI.md — Wiki editoriale
- docs/OLLAMA.md — Guida Ollama
- docs/ADMIN_PANEL.md — Guida admin
- docs/PHP_FPM.md — Regola PHP-FPM
- CONTRIBUTING.md — Pattern + convenzioni
- CHANGELOG.md — Storia modifiche
- SECURITY.md — Policy sicurezza

---

*Documento aggiornato il 15 ottobre 2026*
Motivo: troncare un log mentre si sta ancora verificando un problema distrugge le prove. Copiare prima, poi troncare.

Logrotate: già configurato per laravel.log in /etc/logrotate.d/themedge-laravel (14 giorni, compresso, copytruncate).
