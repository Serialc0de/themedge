# 📚 Guida alle Feature di Themedge

Documentazione completa di tutte le feature avanzate.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [🧠 Knowledge Engine](#-knowledge-engine) *(NUOVO)*
2. [🎙️ TTS Premium](#%EF%B8%8F-tts-premium) *(NUOVO)*
3. [🚨 Breaking News System](#-breaking-news-system)
4. [🔴 Live Blog](#-live-blog)
5. [📱 Push Notifications PWA](#-push-notifications-pwa)
6. [🎯 Sistema Highlight](#-sistema-highlight)
7. [✅ Pacchetto Credibilità](#-pacchetto-credibilità)
8. [🎁 Pacchetto Lettore Premium](#-pacchetto-lettore-premium)
9. [🔍 Sistema Embed](#-sistema-embed)
10. [🤖 AI Locale (Ollama)](#-ai-locale-ollama)

---

## 🧠 Knowledge Engine

**Il cuore unico di Themedge: enciclopedia viva + AI.**

### 📚 Wiki Editoriale

**Auto-generazione da entità** con Ollama.

- **Modelli:** `WikiPage` (6 tipi: person, organization, location, event, concept, topic), `WikiRevision` (versioning)
- **Pivot:** `wiki_page_article`, `wiki_page_entity`
- **Servizio:** `WikiGenerator` (auto-genera bozza con Ollama da entità + articoli)
- **Job:** `GenerateWikiPageFromEntity` (async, ~2 min per wiki)
- **Comando:** `wiki:generate --limit=30 --sync`

**Admin (`/admin/wiki-pages`):**
- CRUD completo con workflow (bozza → pubblicata → archiviata)
- Action "Pubblica" / "Rigenera AI" / "Genera da entità"
- Infobox editabile (KeyValue)
- Alias (nomi alternativi)

**API:**
- `GET /api/wiki` — lista paginata con filtri (type, q)
- `GET /api/wiki/{slug}` — dettaglio + articoli + entità correlate
- `GET /api/wiki/featured` — wiki in evidenza (≥3 articoli)
- `GET /api/wiki/stats` — statistiche
- `GET /api/wiki/search?q=...` — ricerca
- `GET /api/wiki/by-entity/{entitySlug}` — wiki di una entità
- `GET /api/wiki/by-article/{articleSlug}` — wiki collegate a un articolo

**Frontend:**
- `/wiki` — Home con hero, search, stats, featured, categorie, lista
- `/wiki/[slug]` — Dettaglio con markdown render + timeline + articoli + entità
- `/wiki/categoria/[type]` — Filtro per tipo
- `/wiki/cerca` — Risultati ricerca
- SEO JSON-LD (`DefinedTerm`)

### 🕸️ Knowledge Graph Visuale

**Esplorazione visuale delle connessioni.**

- **API:** `GET /api/wiki/graph?limit=150&with_articles=1`
- **Frontend:** `/wiki/grafo` con `react-force-graph-2d`
- **Features:**
  - Force-directed layout
  - Zoom + pan + drag
  - Click nodo → apre wiki/articolo (nuova tab)
  - Hover → tooltip con summary
  - Filtro per tipo (6 tipi wiki)
  - Toggle articoli on/off
  - Search con evidenziazione (nodo rosso)
  - Pulsante "🎯 Adatta alla vista"
  - Legenda colori (7 colori)
  - Node canvas custom (emoji + dimensione + colore)
  - Link particles per relazioni wiki↔wiki

### 💬 AI Chat "Chiedi a Themedge" (RAG)

**Assistente AI con citazioni verificabili, 100% locale.**

**Tecnologia:**
- Embedding: `nomic-embed-text` (768 dim)
- Storage vettoriale: MySQL JSON (no Meilisearch vettoriale)
- Ricerca: cosine similarity in PHP
- Generazione: `qwen2.5:3b` (Ollama, streaming SSE)

**Backend:**
- Migration: `articles.embedding`, `wiki_pages.embedding`
- Servizi: `OllamaEmbeddingService`, `RagSearchService`, `RagPromptBuilder`
- Job: `GenerateArticleEmbedding`, `GenerateWikiEmbedding`
- Comando: `rag:embed-all --type=both --sync`
- Controller: `AskController` (SSE via `fopen`)
- Endpoint: `POST /api/ask` (rate limit 10/min)

**Frontend (`/chiedi`):**
- Chat UI stile ChatGPT (bolle + avatar)
- Streaming token-by-token (~80s per risposta)
- Citazioni `[1]` `[2]` cliccabili → evidenziano la fonte
- Pannello fonti con score % match
- Storico in localStorage (ultimi 20 messaggi)
- Pulsanti 👍/👎 per feedback
- Pulsante "🗑️ Nuova chat"
- 6 domande suggerite

### 📊 Feedback + Analytics

**Ogni query AI tracciata con feedback.**

- **Migration:** `ask_queries` (domanda, risposta, fonti, timing, IP, sessione), `ask_feedbacks` (voto, commento, moderazione)
- **Modelli:** `AskQuery` (hash domanda, scope, contatori), `AskFeedback` (vote_label, isPositive/isNegative)
- **API:** `POST /api/ask/feedback` (rate limit 30/min, anti-spam 5 min per IP)
- **Admin (`/admin/ask-queries`):**
  - Tabella read-only con filtro per feedback negativo
  - Widget `AskStatsOverview`: query totali, % feedback positivi, tempo medio
  - Vista dettaglio con domanda, risposta, fonti, feedback

---

## 🎙️ TTS Premium

**Text-to-Speech con Piper (gratis, offline, qualità alta).**

### Setup

- **Piper 1.8.0** installato via `pipx`
- **Modello:** `it_IT-paola-medium` (~60 MB) in `/opt/piper/voices/`
- **Formato output:** MP3 (via ffmpeg)

### Backend

- **Migration:** `articles.audio_path`, `audio_status`, `audio_duration_seconds`, `audio_model`, `audio_error`, `audio_generated_at`
- **Config:** `config/tts.php` (binary, model, ffmpeg, storage)
- **Servizio:** `PiperTtsService` (synthesize, cleanHtml, runPiper, runFfmpeg)
- **Job:** `GenerateArticleAudio` (queue, retry, recovery se file esiste)
- **Observer:** `ArticleObserver` (auto-dispatch su publish + content changed)
- **Comando:** `tts:test "Ciao mondo" --show`

**Model accessor:**
- `Article::audio_url` — URL pubblico MP3
- `Article::audio_duration_formatted` — "2:54"
- `Article::hasAudio()` — true se pronto
- Scope: `withAudio()`, `withoutAudio()`

**API:**
- `GET /api/articles/{slug}/audio` — metadati (URL, durata, modello, data)

**Admin:**
- Action "🎙️ Genera audio" su ArticleResource
- Colonna "Audio" con badge stato (✅ / ⏳ / ❌)

### Frontend Player Premium (`AudioPlayerPro`)

- Play/pausa, seek bar, skip ±15s, velocità 0.5x-2x, volume
- **MediaSession API** — controlli lock-screen + Bluetooth
- **Salva posizione** in localStorage + banner "Riprendi da..."
- **Sleep timer** (15/30/60 min)
- **Download MP3**
- Sticky on scroll
- SSR-safe (no React #418)

**Componenti:**
- `AudioPlayerPro.tsx` — player principale
- `lib/use-audio-player.ts` — hook stato
- `lib/audio-storage.ts` — utility localStorage

### Config `.env`

```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
TTS_ENABLED=true
PIPER_BINARY=/root/.local/bin/piper
PIPER_MODEL=/opt/piper/voices/it_IT-paola-medium.onnx
TTS_STORAGE_DISK=public
TTS_STORAGE_PATH=audio
TTS_FORMAT=mp3
TTS_AUTO_GENERATE=true
TTS_AUTO_GENERATE_DELAY=30
TTS_AUTO_GENERATE_MIN_LENGTH=200
TTS_AUTO_GENERATE_MAX_LENGTH=50000
```

---

## 🚨 Breaking News System

### Admin

- Pagina impostazioni: `/admin/seo/breaking`
- Widget: Breaking News attive in dashboard
- Azione articolo: "Attiva Breaking" nel menu azioni

### Opzioni

- **Modalità:** Global / Home / Custom / Disabled
- **Stile:** Pulse Red / Solid Red / Black Red / Yellow Warning / Custom
- **Auto-refresh:** ON/OFF (con intervallo in secondi)
- **Dismissibile:** ON/OFF (con ore di persistenza)

### Tipi articolo

| Tipo | Durata | Uso |
|------|--------|-----|
| Flash | 1 ora | Notizia urgentissima |
| Breaking | 24 ore | Notizia importante |
| Developing | 72 ore | Storia in sviluppo |

### Frontend

- Banner in cima al sito (dinamico da API)
- Badge ULTIM'ORA sulle card
- Pagina `/ultimora` con archivio 72h

---

## 🔴 Live Blog

### Admin

- Resource: `/admin/live-blogs`
- Azione: "Vai in diretta" / "Chiudi diretta"
- Aggiornamenti: RelationManager nella pagina di edit

### Tipi aggiornamento

- Aggiornamento (update)
- Ultim'ora (breaking) — invia push
- Analisi (analysis)
- Dichiarazione (quote)
- Foto (photo)
- Video (video)
- Risultato (result)

### Frontend

- `/dirette` — lista dirette live + concluse
- `/diretta/[slug]` — stream live con auto-refresh 30s

---

## 📱 Push Notifications PWA

### Setup (una tantum)

1. Genera VAPID keys: `php artisan tinker` → `VAPID::createVapidKeys()`
2. Aggiungi a `.env`:
   ```
   VAPID_PUBLIC_KEY=...
   VAPID_PRIVATE_KEY=...
   VAPID_SUBJECT=mailto:admin@themedge.com
   ```

### Admin

- Iscritti: `/admin/push-subscriptions`
- Storico invii: `/admin/push-notification-logs`
- Azione: "Invia notifica" (bulk)

### Integrazione automatica

- Attivazione breaking news → push automatica
- Live update tipo "breaking" → push automatica

### Frontend

- Pulsante nel footer (variants: button, banner, compact)
- Rileva iOS (necessita installazione PWA)
- Service worker `/sw.js`

### Supporto

| Piattaforma | Push |
|-------------|------|
| Chrome Android | ✅ |
| Safari iOS 16.4+ | ✅ (solo PWA installata) |
| Chrome Desktop | ✅ |
| Firefox Desktop | ✅ |

---

## 🎯 Sistema Highlight

**Livello NYT-level.** Selezione testo + SVG overlay (non tocca HTML).

### Feature

- Selezione testo + popup
- 3 colori + note personali + reazioni immutabili (emoji)
- SVG overlay (non tocca HTML)
- Fuzzy match W3C-style (resiste a modifiche articolo)
- Rilevamento highlight orfani
- Heatmap articolo + top fragments
- Condivisione come immagine PNG
- Admin moderazione + ban reader
- Policy a 3 livelli (articolo, categoria, autore)
- Override JSON granulari
- Scadenza temporale

### Reader Anonimo

- Token device-based (localStorage)
- GDPR: export/delete in `/privacy/dati-personali`

### Admin

- `/admin/highlights` — moderazione
- `/admin/highlight-policies` — policy

### Frontend

- `/highlight` — highlight personali
- Layer SVG sopra articolo
- Popup dettaglio + reactions

---

## ✅ Pacchetto Credibilità

### Correzioni Pubbliche

- Inline nell'articolo (badge + diff before/after)
- Severità (minor, major, critical)
- Data pubblicazione
- `/correzioni` — archivio pubblico

### Fonti Verificabili

- 8 tipi (official, news, academic, social, ecc.)
- URL + titolo + publisher + data + nota
- Archivio Wayback opzionale
- API: `/api/articles/{slug}/sources`
- Admin: repeater in ArticleResource

### Approfondimenti Curati

- Many-to-many articoli (related)
- Nota editoriale per ogni relazione
- API: `/api/articles/{slug}/curated`

### Fact-Check

- Claim + verdict (verified, in_review, false)
- Fonte + nota
- Badge visivi (✅ ⚠️ ❌)
- API: `/api/articles/{slug}/fact-checks`

### Claim AI (auto-detection)

- Estrazione claim con Ollama (`llama3.1:8b`)
- Migration `article_claims`
- Admin: Action "Rileva claim" + Livewire ClaimsPanel
- Kill switch: `FACTCHECK_AI_ENABLED=false`

### Knowledge Box

- "Cosa sappiamo / Cosa non sappiamo / Stiamo verificando"
- 3 tipi con icone (✅ ❓ 🔍)

### Timeline Contesto

- Eventi storici collegati all'articolo
- Data + titolo + descrizione + fonte
- `is_key_event` per evidenziare

### Glossario Editoriale

- Termini tecnici con definizione
- Auto-link nel testo (tooltip)
- Categorie + alias
- `/glossario` + `/glossario/[slug]`

### Entità (Knowledge Graph)

- 5 tipi: person, organization, location, event, concept
- Auto-detection con Ollama (`EntityExtractor`)
- Auto-link nel testo
- Pagine `/entita` + `/entita/[slug]`

### AI Disclosure

- Badge trasparenza uso AI
- 4 livelli: none, assisted, generated, edited
- Visibile nell'articolo + API

---

## 🎁 Pacchetto Lettore Premium

### Reading List

- Salva articoli per dopo
- Token reader anonimo (localStorage)
- `/salvati` — lista personale
- Icona bookmark su card

### Share

- Web Share API (mobile nativo)
- Fallback: copia link + social (Twitter, WhatsApp, LinkedIn)
- Share highlight come PNG

### Scrollytelling

- 11 tipi di sezione: hero, text, image, sticky-image, quote, stat, chart, video, timeline, map, gallery
- Animazioni GSAP
- Pagina `/articolo/[slug]/scrolly`
- Admin: repeater in ArticleResource

---

## 🔍 Sistema Embed

### Provider

| Provider | Tipo | GDPR |
|----------|------|------|
| YouTube | Video | ✅ (nocookie) |
| Vimeo | Video | ✅ |
| Spotify | Audio | ✅ |
| Twitter/X | Post | ⚠️ |
| Instagram | Post | ⚠️ |
| TikTok | Video | ⚠️ |
| Generic | iframe | ⚠️ |

### Backend

- `HtmlSanitizer` (symfony/html-sanitizer)
- `OEmbedResolver` (8 provider)
- `GET /api/oembed?url=...`
- `GET /api/oembed/providers`
- Hook `Article::saving()` per sanitizzazione automatica
- Comando `articles:resanitize --dry-run`

### Frontend

- `ArticleBody` con parser embed + sanitizzazione output
- Componenti: `VideoEmbed`, `SpotifyEmbed`, `GenericEmbed`, `TwitterEmbed`, `InstagramEmbed`, `TikTokEmbed`
- VideoEmbed con click-to-load + thumbnail YouTube

### Auto-embed URL

- URL YouTube/Vimeo **nudi** nel testo → convertiti automaticamente in iframe
- `Article::autoEmbedUrls()` (v6)
- Chiusura/riapertura `<p>` + `<figure>` semantico
- Boundary robusto (gestisce `<p>URL</p>`, "Testo: URL", ecc.)

### Sicurezza

- Doppia sanitizzazione (input + output)
- Blocco SSRF: iframe verso IP interni/localhost rimossi
- Blocco XSS: script, event handlers, javascript: URL
- Allow-list iframe host
- Force HTTPS

---

## 🤖 AI Locale (Ollama)

**8 feature AI, tutte locali, zero costi.**

| Feature | Modello | Dove |
|---------|---------|------|
| TL;DR Summary | `llama3.1:8b` | `/admin/articles` |
| Entity Detection | `qwen2.5:3b` | `/admin/articles` |
| SEO Optimizer | `qwen2.5:3b` | `/admin/articles` |
| Fact-check Claim | `llama3.1:8b` | `/admin/articles` |
| Wiki Generation | `llama3.1:8b` | `/admin/wiki-pages` |
| Embedding (RAG) | `nomic-embed-text` | `/admin/ask-queries` |
| Chat RAG | `qwen2.5:3b` | `/chiedi` |
| TTS | Piper | `/articolo/[slug]` |

**Modelli installati:**
- `llama3.1:8b` (4.9 GB) — qualità top
- `qwen2.5:3b` (1.9 GB) — veloce, consigliato
- `nomic-embed-text` (275 MB) — embedding
- `qwen2:0.5b` (400 MB) — velocissimo, fallback

**Config:**
```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=llama3.1:8b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
FACTCHECK_AI_ENABLED=true
```

---

*Ultima modifica: 14 ottobre 2026*
