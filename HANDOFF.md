# 🤝 Handoff — Progetto Themedge

**Data ultima modifica:** 14 ottobre 2026
**Ore totali:** ~76
**Stato:** ~99% completo
**Ultimo commit:** `42b2dc6`
**Repo:** https://github.com/Serialc0de/themedge
**Sito:** https://themedge.com · **Admin:** https://admin.themedge.com/admin
**VPS:** 178.83.121.96 (Ubuntu 26.04, 4 CPU, 7.75 GB RAM, 38 GB disco)

---

## 🚨 Regole CRITICHE — MAI VIOLARE

### 1. PHP-FPM — MAI `systemctl`

Il servizio `php-fpm-84.service` è **masked** in systemd. Usare `systemctl restart` causa **crash loop**.

**Usare SEMPRE lo script dedicato:** `[FPM_SCRIPT]` (alias: il percorso reale sul server)

### 2. OPcache validate_timestamps = Off

Dopo ogni modifica PHP → eseguire `[FPM_SCRIPT]`.

### 3. Next.js — SEMPRE `npm run build` prima di `pm2 restart`

Mai `pm2 restart themedge-web` da solo: Next.js perde `BUILD_ID` → crash loop.

**Sequenza corretta:**

```bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npm run build
pm2 restart themedge-web --update-env
```

### 4. `pm2 stop` PRIMA della build critica

```bash
cd /www/wwwroot/themedge.com/web
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
```

### 5. Modifica in PRODUZIONE, sync nel repo

Mai modificare direttamente `/www/wwwroot/themedge-repo/`.

**Flusso:**

1. Modifica in `/www/wwwroot/themedge.com/`
2. `rsync` verso `/www/wwwroot/themedge-repo/`
3. `git commit` + `git push`

### 6. Timeout Ollama

```bash
OLLAMA_TIMEOUT=600
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
```

**⚠️ RAM:** `llama3.1:8b` (5.6 GB) **non entra** se altri processi occupano RAM. Usare `qwen2.5:3b` (2.2 GB) per RAG.

### 7. Queue worker

Il worker PM2 `themedge-queue` gira in background.

- ❌ Mai riavviarlo durante un job AI
- ✅ Dopo modifiche → `pm2 restart themedge-queue`

### 8. MAI `php artisan tinker --execute` con quote escape

**Rompe il terminale SSH** (resta in attesa di chiusura quote).

**Usare SEMPRE file PHP temporanei:**

```bash
cat > /tmp/script.php << 'PHPEOF'
<?php
require __DIR__ . '/../../www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once __DIR__ . '/../../www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// codice
PHPEOF
php /tmp/script.php
```

### 9. MAI `sed` su JSX/TSX

Rompe la sintassi. Usare `nano` a mano o `cat > file << 'EOF'`.

### 10. MAI `npm run dev` in produzione

Scrive in `.next/` e contamina la build di produzione.

### 11. `cp -r` può creare path duplicati

`cp -r src/ dest/` se `dest/src/` esiste → `dest/src/src/`. **Verificare con `find`**.

### 12. Timestamp migration diversi

Due migration con stesso secondo → Laravel le ordina alfabeticamente. Usare timestamp progressivi.

### 13. Cron — MAI `echo "..." | crontab -`

**Sovrascrive tutti i cron.** Usare `crontab -e`.

**Backup prima:** `crontab -l > /root/crontab.bak.$(date +%s)`

---

## 📁 STRUTTURA PROGETTO

```
/www/wwwroot/themedge.com/
├── api/                            Laravel 13 + Filament 3
│   ├── app/
│   │   ├── Filament/
│   │   │   ├── Resources/          19 Resource
│   │   │   ├── Widgets/            16 Widget
│   │   │   └── Pages/
│   │   ├── Http/Controllers/Api/   15+ Controller
│   │   ├── Jobs/                   GenerateArticleAudio, WikiPage, Embedding
│   │   ├── Livewire/
│   │   ├── Models/                 22+ modelli
│   │   ├── Observers/              ArticleObserver
│   │   ├── Console/Commands/       6+ comandi
│   │   └── Services/
│   │       ├── Llm/                OllamaClient, ClaimExtractor, EntityExtractor
│   │       ├── Wiki/               WikiGenerator
│   │       ├── Rag/                OllamaEmbedding, RagSearch, RagPromptBuilder
│   │       └── Tts/                PiperTtsService
│   ├── config/
│   │   ├── services.php
│   │   ├── tts.php
│   │   └── highlights.php
│   ├── database/migrations/        54+ migration
│   └── routes/api.php              60+ endpoint
├── web/                            Next.js 16
│   ├── app/
│   │   ├── wiki/                   Home, dettaglio, categoria, cerca, grafo
│   │   ├── chiedi/                 AI Chat RAG
│   │   ├── articolo/[slug]/
│   │   └── ...
│   ├── components/
│   │   ├── article/                AudioPlayerPro, Highlight*, ecc.
│   │   ├── ask/                    AskChat, AskMessage, AskSources, ecc.
│   │   ├── wiki/                   WikiCard, WikiGraph, WikiContent, ecc.
│   │   └── ...
│   ├── lib/
│   │   ├── wiki.ts
│   │   ├── ask.ts
│   │   ├── audio-storage.ts
│   │   └── use-audio-player.ts
│   └── .env.local
└── storage/
```

---

## 🗄️ DATABASE — MODELLI PRINCIPALI

### Editoriale

- `Article`, `User`, `Category`, `Tag`, `Comment`, `MediaAsset`, `Redirect`

### Trust & Credibilità

- `ArticleSource`, `Correction`, `ArticleFactCheck`, `ArticleClaim`
- `ArticleKnowledgeItem`, `ArticleTimelineEvent`, `GlossaryTerm`
- `Entity`, `ScrollySection`

### Knowledge Engine (14 ott)

- `WikiPage` — 6 tipi, versioning
- `WikiRevision` — revisioni con diff

### AI & RAG (14 ott)

- `AskQuery` — query AI con risposta, fonti, timing
- `AskFeedback` — voti 👍/👎

### Campi aggiunti

- `Article`: `audio_*`, `embedding`, `embedding_model`, `embedding_generated_at`
- `WikiPage`: `embedding`, `embedding_model`, `embedding_generated_at`

---

## 🎯 FEATURE IMPLEMENTATE

### 14 ottobre — Sessione 1 (React #418 + Auto-embed)

- `ShareButtons.tsx` SSR-safe (mounted + useEffect)
- `autoEmbedUrls` v6 con `<figure>` semantico
- `VideoEmbed.tsx` con `<noscript>` + `<img>` nativo
- Pulizia 40+ `.bak`, 240 MB `api-broken-*`, migration duplicata

### 14 ottobre — Sessione 2 (TTS Premium)

- Piper 1.8.0 + modello `it_IT-paola-medium`
- Migration `articles.audio_*`
- `PiperTtsService` + Job + Observer
- API `GET /api/articles/{slug}/audio`
- `AudioPlayerPro` (MediaSession, save position, sleep timer, download)

### 14 ottobre — Sessione 3 (Wiki Editoriale)

- Migration `wiki_pages`, `wiki_revisions`, pivot
- `WikiGenerator` (Ollama auto-genera)
- Admin `WikiPageResource`
- API (7 endpoint)
- Frontend `/wiki` + varianti
- Markdown render + SEO JSON-LD

### 14 ottobre — Sessione 4 (Knowledge Graph)

- API `GET /api/wiki/graph`
- Frontend `/wiki/grafo` con `react-force-graph-2d`
- Zoom/pan/drag/click/hover + filtri + search + legenda

### 14 ottobre — Sessione 5 (AI Chat RAG)

- Embedding `nomic-embed-text` (MySQL JSON)
- `RagSearchService` + `RagPromptBuilder`
- `AskController` SSE streaming
- Frontend `/chiedi` con citazioni `[N]` cliccabili

### 14 ottobre — Sessione 6 (Feedback + Analytics)

- Migration `ask_queries`, `ask_feedbacks`
- API `POST /api/ask/feedback`
- Admin dashboard Filament (`AskQueryResource` + widget)

### Precedenti (fino al 12 ott)

- Sistema Embed (8 provider)
- Sistema Highlight NYT-level
- Pacchetto Credibilità (correzioni, fonti, curate)
- Pacchetto Lettore Premium (reading list, share)
- Admin Panel (30+ feature)
- Frontend Next.js (30+ pagine)
- Sicurezza (SSRF/XSS, HTTPS, rate limiting)
- Performance (TTFB 30-80ms, Cloudflare cache)

---

## 🤖 OLLAMA — CONFIGURAZIONE

### Modelli installati

| Modello | Dimensione | Uso |
|---------|-----------|-----|
| `llama3.1:8b` | 4.9 GB | Qualità top (sconsigliato su RAM bassa) |
| `qwen2.5:3b` | 1.9 GB | RAG e extraction (consigliato) |
| `nomic-embed-text` | 275 MB | Embedding per RAG |

### Config `.env`

```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
OLLAMA_EMBEDDING_TIMEOUT=60
FACTCHECK_AI_ENABLED=true
```

### Kill switch

```bash
FACTCHECK_AI_ENABLED=false
php artisan config:clear
```

### Comandi utili

```bash
ollama list              # lista modelli
ollama ps                # modelli in RAM
ollama stop llama3.1:8b  # ferma modello
```

---

## 📋 COMANDI OPERATIVI QUOTIDIANI

### Riavvio completo

```bash
# PHP-FPM
[FPM_SCRIPT]

# Next.js
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npm run build
pm2 restart themedge-web --update-env

# Queue worker
pm2 restart themedge-queue

# Cache Laravel
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
```

### Purge Cloudflare

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

### Backup manuale

```bash
/root/backup-themedge.sh
ls -lh /root/backups/themedge/
```

### Comandi AI/Wiki/RAG

```bash
cd /www/wwwroot/themedge.com/api

# Genera wiki da entità
php artisan wiki:generate --limit=30
php artisan wiki:generate --entity=5 --sync

# Genera embedding
php artisan rag:embed-all --type=both --sync

# Test TTS
php artisan tts:test "Ciao mondo" --show
```

---

## 🚨 COMANDI DI EMERGENZA

### Sito 500

```bash
pm2 list
[FPM_SCRIPT]
systemctl status nginx --no-pager
tail -50 /www/wwwroot/themedge.com/api/storage/logs/laravel.log
pm2 logs themedge-web --lines 50 --nostream

cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
```

### Next.js non parte

```bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
ls -la .next/BUILD_ID
# Se manca:
npm run build
pm2 restart themedge-web --update-env
```

### PHP-FPM crash loop

```bash
/etc/init.d/php-fpm-84 stop
pkill -9 -f php-fpm
rm -f /tmp/php-cgi-84.sock
/www/server/php/84/sbin/php-fpm --daemonize
```

### Queue worker fermo

```bash
pm2 list | grep themedge-queue
pm2 restart themedge-queue
```

### RAM saturata (Ollama)

```bash
ollama ps
ollama stop llama3.1:8b
ollama stop qwen2.5:3b
free -h
```

### Disco pieno

```bash
df -h /
find /www/wwwroot/themedge.com -name "*.bak*" -mtime +3 -delete
pm2 flush
> /www/wwwroot/themedge.com/api/storage/logs/laravel.log
journalctl --vacuum-time=7d
```

### React #418 in console

Pattern SSR-safe obbligatorio:

```tsx
const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);

const value = mounted && typeof window !== 'undefined' ? ... : fallback;
```

### Errore permessi

```bash
chown -R www:www /www/wwwroot/themedge.com/api
chown -R www:www /www/wwwroot/themedge.com/web
chmod -R 775 /www/wwwroot/themedge.com/api/storage
chmod -R 775 /www/wwwroot/themedge.com/api/bootstrap/cache
```

---

## 📊 ROADMAP FUTURA

### Sessione 7 — Paywall / Stripe (6-8h)

- Stripe Checkout + Webhook
- Metered paywall (3 articoli gratis/mese)
- Flag articolo premium
- Area abbonati

### Sessione 8 — Newsletter + Email (5-6h)

- SMTP setup (Brevo/Mailgun)
- Notifiche commenti
- Newsletter editor (template)
- Segmentazione lettori

### Sessione 9 — OAuth Google (3-4h)

- Google Cloud Console setup
- Login + migrazione token reader

### Sessione 10 — Rifiniture (5-10h)

- Versioning UI Wiki (diff visuale)
- SEO Wiki avanzato (sitemap, RSS)
- Cache Redis RAG
- Podcast RSS auto-generato

---

## 🏆 STATISTICHE PROGETTO

| Metrica | Valore |
|---------|--------|
| Ore di lavoro | ~76 |
| Giorni | 7 |
| Pagine frontend | 30+ |
| API endpoints | 60+ |
| Modelli Eloquent | 22+ |
| Filament Resources | 19 |
| Filament Pages | 7 |
| Filament Widgets | 16 |
| Migration | 54+ |
| Componenti React | 80+ |
| Commit | ~50 |
| Feature AI | 8 |

---

## ✅ CHECK INIZIALE — COSA FARE ALL'INIZIO

```bash
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
[FPM_SCRIPT]

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
```

---

## 🎁 BENVENUTO

Erediti un progetto **~99% completo** con:

- 📰 Giornale online editoriale professionale
- 🧠 Knowledge Engine unico in Italia (Wiki + Graph + AI Chat)
- 🎙️ TTS Premium (Piper, gratis, offline)
- 🤖 AI locale (Ollama, zero costi)
- ⚡ Performance TTFB 30-80ms
- 📱 PWA + push notifications
- 🌐 Cloudflare CDN + WAF
- 📊 Analytics + feedback
- 🔒 GDPR compliance

Buon lavoro! 🚀

---

## 🔑 CREDENZIALI

Vedi file sul server (NON versionati):

- MySQL: `/root/.my.cnf`
- Redis: `/www/server/redis/redis.conf`
- Meilisearch: `/root/.meilisearch_master_key`
- GitHub token: `/root/.github-token`
- Laravel APP_KEY: `/www/wwwroot/themedge.com/api/.env`
- VAPID keys: `/www/wwwroot/themedge.com/api/.env`
- Cloudflare token: `/www/wwwroot/themedge.com/api/.env`
- Admin panel: `admin@themedge.com`

Utenti di test:

- `editor@themedge.com` → `EditorPassword123!`
- `test.editor@themedge.com` → `AuthorPassword123!`

**NON committare MAI queste credenziali su GitHub.**

---

## 📞 URL UTILI

- GitHub: https://github.com/Serialc0de/themedge
- Sito: https://themedge.com
- Admin: https://admin.themedge.com/admin
- API: https://api.themedge.com/api
- Cloudflare: https://dash.cloudflare.com

---

## 📚 Documentazione

- `docs/ADMIN_PANEL.md` — Guida pannello admin
- `docs/FEATURES.md` — Guida feature avanzate
- `docs/PHP_FPM.md` — Regola PHP-FPM
- `docs/TROUBLESHOOTING.md` — Problemi noti
- `docs/DEPLOY.md` — Deploy + rollback
- `docs/SETUP.md` — Setup dev
- `CHANGELOG.md` — Storia modifiche
- `SECURITY.md` — Vulnerabilità documentate

---

*Ultima modifica: 14 ottobre 2026*
