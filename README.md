# 📰 Themedge

Giornale online editoriale professionale con **Knowledge Engine** e **AI locale**.

**Sito:** [themedge.com](https://themedge.com) · **Admin:** [admin.themedge.com/admin](https://admin.themedge.com/admin)

---

## 🎯 Panoramica

Themedge è una piattaforma editoriale completa con:

### 📰 Editoriale
- **Backend Laravel 13 + Filament 3** con pannello admin professionale
- **Frontend Next.js 16** con tema editoriale raffinato
- **Workflow editoriale** completo (draft → review → approved → published → archived)
- **Breaking News** con banner + push notifications
- **Live Blog** per dirette in tempo reale
- **Commenti** con moderazione
- **Analytics Editoriale** con KPI e report
- **Contenuto editoriale** (sondaggi, correzioni, segnalazioni, redazione)

### 🧠 Knowledge Engine (UNICO in Italia)
- **📚 Wiki Editoriale** auto-alimentata dagli articoli (Ollama)
- **🕸️ Knowledge Graph** visuale interattivo (force-directed)
- **💬 Chiedi a Themedge** — AI Chat RAG con citazioni verificabili
- **📊 Feedback + Analytics** su ogni query AI

### 🎙️ Audio Premium
- **Text-to-Speech** con Piper (voce italiana `Paola`, gratis, offline)
- **Player Premium** con MediaSession, salva posizione, sleep timer, download

### 🔍 Ricerca & SEO
- **Ricerca full-text** con Meilisearch (1ms)
- **SEO completo** (sitemap, robots, OpenGraph, JSON-LD, RSS)

### 📱 PWA & Push
- **Push Notifications PWA** cross-platform
- **Installabile** su mobile/desktop

### 🤖 AI Locale (zero costi)
- **Ollama** con modelli `llama3.1:8b`, `qwen2.5:3b`, `nomic-embed-text`
- **8 feature AI**: TL;DR, Entity detection, SEO, Fact-check, Wiki generation, RAG Chat, Embedding, TTS

### 📡 API & Infra
- **API REST** con Sanctum (60+ endpoint)
- **Cloudflare CDN + WAF** + purge automatico
- **Backup + cron + monitoring** produzione

---

## 🏗️ Stack Tecnico

| Componente | Tecnologia |
|---|---|
| **Backend** | Laravel 13 + Filament 3 + PHP 8.4 |
| **Frontend** | Next.js 16 + Tailwind CSS v4 |
| **Database** | MySQL 8 + Redis 7 |
| **Ricerca** | Meilisearch 1.54 |
| **Server** | Nginx 1.30 + PHP-FPM 8.4 (aaPanel) |
| **CDN/WAF** | Cloudflare (Full strict + HSTS) |
| **Process Manager** | PM2 (themedge-web + themedge-queue) |
| **CI/CD** | GitHub Actions + Dependabot |
| **AI Locale** | Ollama (llama3.1:8b, qwen2.5:3b, nomic-embed-text) |
| **TTS** | Piper 1.8.0 (voce it_IT-paola-medium) |
| **Search Vettoriale** | MySQL JSON + cosine similarity (PHP) |
| **Markdown** | react-markdown + remark-gfm |
| **Graph** | react-force-graph-2d |

---

## 📁 Struttura

```
themedge/
├── api/                    Laravel 13 + Filament 3
│   ├── app/
│   │   ├── Filament/       Resources, Widgets, Pages
│   │   ├── Models/         22+ modelli Eloquent
│   │   ├── Http/Controllers/Api/
│   │   ├── Jobs/           Audio, Wiki, Embedding
│   │   ├── Observers/      ArticleObserver
│   │   ├── Console/Commands/
│   │   └── Services/
│   │       ├── Llm/        OllamaClient, ClaimExtractor, EntityExtractor
│   │       ├── Wiki/       WikiGenerator
│   │       ├── Rag/        OllamaEmbedding, RagSearch, RagPromptBuilder
│   │       └── Tts/        PiperTtsService
│   ├── config/
│   │   ├── services.php    Cloudflare, Ollama, VAPID
│   │   ├── tts.php         Piper, FFmpeg, storage
│   │   └── highlights.php
│   ├── database/migrations/  54+ migration
│   └── routes/api.php      60+ endpoint
├── web/                    Next.js 16
│   ├── app/
│   │   ├── wiki/           Home, dettaglio, categoria, cerca, grafo
│   │   ├── chiedi/         AI Chat RAG
│   │   └── articolo/[slug]/
│   ├── components/
│   │   ├── article/        AudioPlayerPro, Highlight*, ArticleTLDR
│   │   ├── ask/            AskChat, AskMessage, AskSources
│   │   └── wiki/           WikiCard, WikiGraph, WikiContent
│   ├── lib/
│   │   ├── wiki.ts
│   │   ├── ask.ts
│   │   ├── audio-storage.ts
│   │   └── use-audio-player.ts
│   └── public/
├── docs/                   Documentazione
├── .github/workflows/      CI/CD
└── README.md
```

---

## 🧠 Knowledge Engine — Le 3 feature uniche

### 📚 Wiki Editoriale

Enciclopedia viva auto-generata dagli articoli.

- **URL:** https://themedge.com/wiki
- **Auto-generazione** con Ollama da entità (person, org, location, event, concept, topic)
- **Versioning** completo di ogni modifica
- **Cross-linking** articoli ↔ wiki
- **Markdown render** + infobox strutturato
- **SEO JSON-LD** (`DefinedTerm`)
- **TTS integrato** su ogni pagina

**Admin:** `/admin/wiki-pages` — CRUD + action "Pubblica" / "Rigenera AI" / "Genera da entità"

**Command:**
```bash
php artisan wiki:generate --limit=30
php artisan wiki:generate --entity=5 --sync
```

### 🕸️ Knowledge Graph

Grafo interattivo delle connessioni tra wiki e articoli.

- **URL:** https://themedge.com/wiki/grafo
- **Force-directed layout** (`react-force-graph-2d`)
- **Zoom + pan + drag** + **click nodo** → apre contenuto
- **Filtri** per tipo + **search** con evidenziazione + **legenda** colori
- **Toggle articoli** on/off

### 💬 Chiedi a Themedge (AI Chat RAG)

Assistente AI con citazioni verificabili, 100% locale.

- **URL:** https://themedge.com/chiedi
- **RAG** su articoli + wiki
- **Streaming** token-by-token (SSE)
- **Citazioni** `[1]` `[2]` cliccabili → evidenziano la fonte
- **Pannello fonti** con score % match
- **Feedback** 👍/👎 (analytics admin)
- **100% locale** (Ollama, zero costi)

**Admin:** `/admin/ask-queries` — dashboard con analytics + feedback

---

## 🎙️ TTS Premium

Text-to-Speech con Piper (gratis, offline, voce italiana).

- **Modello:** `it_IT-paola-medium` (63 MB)
- **Formato:** MP3 (via ffmpeg)
- **Auto-generazione** al salvataggio articolo
- **Player Premium:** MediaSession, save position, sleep timer, download

**Admin:** `/admin/articles` → Action "🎙️ Genera audio"

**Command:**
```bash
php artisan tts:test "Ciao mondo" --show
```

**API:**
```
GET /api/articles/{slug}/audio
```

---

## 🎛️ Pannello Admin

Accesso: admin.themedge.com/admin

### Funzionalità principali

**Contenuti:**
- Articoli con workflow editoriale completo
- Categorie e tag
- Media Library con upload multiplo
- Redirect manager (301/302)
- Breaking News configurabili
- Live Blog con stream in tempo reale
- Sondaggi, Correzioni, Commenti
- **Wiki editoriale** (NUOVO)

**Redazione:**
- Utenti con avatar, bio, ruoli
- Squadra editoriale, Ruoli editoriali
- Segnalazioni pubbliche

**SEO:**
- Impostazioni SEO globali
- Robots.txt editor, Sitemap
- Breaking News settings

**AI & Analytics:**
- **Ask Query dashboard** (NUOVO)
- Analytics Editoriale
- Push Subscriptions
- Audit Log

**Sistema:**
- Dashboard con 16+ widget
- Cron + Backup + Monitoring

### Ruoli (Shield)

| Ruolo | Permessi | Descrizione |
|---|---|---|
| super_admin | 55 (tutti) | Amministratore |
| editor | 36 | Gestisce tutti gli articoli |
| author | 10 | Crea/modifica i propri articoli |
| contributor | 4 | Crea solo bozze |

Vedi [docs/ADMIN_PANEL.md](docs/ADMIN_PANEL.md) per la guida completa.

---

## 🌐 Frontend Pubblico

**Editoriale:**
- `/` — Homepage con hero, sezioni, più letti
- `/articolo/[slug]` — Articolo con TTS, commenti, correlati
- `/categoria/[slug]`, `/tag/[slug]`, `/autore/[id]`
- `/cerca` — Ricerca istantanea Meilisearch
- `/ultimora` — Archivio breaking news
- `/dirette`, `/diretta/[slug]` — Live Blog
- `/redazione`, `/correzioni`, `/segnala`

**Knowledge Engine (NUOVO):**
- `/wiki` — Home wiki
- `/wiki/[slug]` — Dettaglio pagina
- `/wiki/categoria/[type]` — Filtro per tipo
- `/wiki/cerca` — Ricerca wiki
- `/wiki/grafo` — Knowledge Graph visuale
- `/chiedi` — AI Chat RAG

**Utente:**
- `/salvati` — Reading list
- `/highlight` — Highlight personali
- `/privacy/dati-personal` — GDPR

**Sistema:**
- `/rss`, `/rss/[category]`
- `/sitemap.xml`, `/robots.txt`

---

## 🚀 Quick Start (sviluppo)

### Prerequisiti

- PHP 8.3+
- Composer
- Node.js 20+ (consigliato 22)
- MySQL 8+
- Redis 7+
- Meilisearch 1.6+
- Ollama (per AI)
- Piper (per TTS)
- ffmpeg

### Backend Laravel

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate

# Configura .env (DB, Redis, Meilisearch, Ollama, Piper)
nano .env

php artisan migrate --seed
php artisan storage:link
php artisan make:filament-user
php artisan serve
```

### Frontend Next.js

```bash
cd web
npm install
cp .env.example .env.local
npm run dev
```

### AI (opzionale ma consigliato)

```bash
# Ollama
curl -fsSL https://ollama.com/install.sh | sh
ollama pull qwen2.5:3b
ollama pull nomic-embed-text

# Piper TTS
pipx install piper-tts
# Scarica modello (vedi docs/SETUP.md)
```

**Vedi [docs/SETUP.md](docs/SETUP.md) per la guida completa.**

---

## 🚨 PHP-FPM — Regola Critica

**MAI** usare `systemctl restart php-fpm-84` → crash loop garantito.

**SEMPRE** usare:

```bash
/root/fpm.sh
```

Vedi [docs/PHP_FPM.md](docs/PHP_FPM.md) per i dettagli.

---

## 🔧 Comandi Utili

### Riavvio PHP-FPM

```bash
/root/fpm.sh
```

### Cache Laravel + Filament

```bash
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
```

### Rebuild frontend

```bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npm run build
pm2 restart themedge-web --update-env
```

### Comandi AI

```bash
cd /www/wwwroot/themedge.com/api

# Genera wiki da entità
php artisan wiki:generate --limit=30

# Genera embedding per RAG
php artisan rag:embed-all --type=both --sync

# Test TTS
php artisan tts:test "Ciao mondo" --show
```

### Backup

```bash
/root/backup-themedge.sh
```

### Purge Cloudflare

```bash
cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

---

## 📚 Documentazione

| File | Cosa |
|------|------|
| [HANDOFF.md](HANDOFF.md) | **Passaggio nuovo operatore** (regole, struttura, workflow) |
| [docs/SETUP.md](docs/SETUP.md) | Setup completo (Ollama, Piper, Meilisearch) |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Deploy + rollback |
| [docs/FEATURES.md](docs/FEATURES.md) | Tutte le feature (13 sezioni) |
| [docs/ADMIN_PANEL.md](docs/ADMIN_PANEL.md) | Guida pannello admin |
| [docs/RAG.md](docs/RAG.md) | Knowledge Engine AI (Wiki + Graph + Chat) |
| [docs/WIKI.md](docs/WIKI.md) | Wiki editoriale completa |
| [docs/OLLAMA.md](docs/OLLAMA.md) | Guida Ollama (modelli, performance) |
| [docs/PHP_FPM.md](docs/PHP_FPM.md) | Regola critica PHP-FPM |
| [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) | Problemi noti + fix |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Pattern + convenzioni |
| [CHANGELOG.md](CHANGELOG.md) | Storia modifiche |
| [SECURITY.md](SECURITY.md) | Vulnerabilità documentate |

---

## 📊 Statistiche Progetto

| Metrica | Valore |
|---------|--------|
| Ore di lavoro | ~76 |
| Giorni | 7 |
| Pagine frontend | 30+ |
| API endpoints | 60+ |
| Modelli Eloquent | 22+ |
| Filament Resources | 19 |
| Filament Widgets | 16 |
| Migration | 54+ |
| Componenti React | 80+ |
| Feature AI | 8 |

---

## 📝 Changelog

Vedi [CHANGELOG.md](CHANGELOG.md).

---

## 📄 Licenza

Vedi [LICENSE](LICENSE).
