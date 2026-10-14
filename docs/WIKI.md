# 📚 Wiki — Knowledge Engine

Guida completa al sistema Wiki editoriale di Themedge.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Panoramica](#panoramica)
2. [Architettura](#architettura)
3. [Modelli](#modelli)
4. [Auto-generazione](#auto-generazione)
5. [Admin](#admin)
6. [API](#api)
7. [Frontend](#frontend)
8. [Knowledge Graph](#knowledge-graph)
9. [Troubleshooting](#troubleshooting)

---

## Panoramica

**Themedge Wiki** è un'enciclopedia viva che si auto-genera dagli articoli pubblicati. Ogni entità (persona, luogo, organizzazione, evento, concetto) ha una pagina wiki che cresce nel tempo.

**Nessun giornale italiano ha una feature simile.**

**Caratteristiche:**

- ✅ **Auto-generazione** da entità con Ollama
- ✅ **Versioning completo** (ogni modifica tracciata)
- ✅ **Cross-linking** articoli ↔ wiki
- ✅ **Markdown render** + infobox strutturato
- ✅ **Knowledge Graph** visuale interattivo
- ✅ **TTS integrato** (audio su ogni pagina)
- ✅ **SEO JSON-LD** (`DefinedTerm`)
- ✅ **Ricerca full-text** (Meilisearch)

---

## Architettura

```
┌─────────────────────────────────────────────────────────┐
│  ARTICOLI                                               │
│  ────────                                               │
│  Article → entities (many-to-many)                      │
│         → timeline_events                               │
│         → sources                                       │
└─────────────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│  WIKI GENERATOR                                         │
│  ──────────────                                         │
│  1. Prende Entity (person, org, location, ...)          │
│  2. Trova articoli che la menzionano                    │
│  3. Ollama genera: summary, content, infobox            │
│  4. Crea WikiPage (status: draft)                       │
│  5. Collega articoli (pivot con relevance)              │
└─────────────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│  WIKI PAGE                                              │
│  ─────────                                              │
│  Editor rivede → Pubblica → Visibile su /wiki/[slug]    │
└─────────────────────────────────────────────────────────┘
```

---

## Modelli

### WikiPage

**Tabella:** `wiki_pages`

**Campi principali:**

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | bigint | PK |
| `slug` | string(191) | URL-friendly (unique) |
| `title` | string | Titolo |
| `subtitle` | string | Sottotitolo (es. "Papa 2025–") |
| `type` | string(30) | person, organization, location, event, concept, topic |
| `status` | string(20) | draft, published, archived |
| `summary` | text | Riassunto breve (1-2 frasi) |
| `content` | longText | Contenuto markdown |
| `infobox` | json | Dati strutturati |
| `aliases` | json | Nomi alternativi |
| `icon` | string(10) | Emoji |
| `featured_image` | string | URL immagine |
| `articles_count` | int | Cache contatore |
| `views` | int | Visualizzazioni |
| `is_ai_generated` | bool | Generato da AI |
| `ai_model` | string(100) | Modello usato |
| `entity_id` | bigint | FK entità collegata |

**Tipi (`WikiPage::TYPE_LABELS`):**

```php
'person' => '👤 Persona',
'organization' => '🏛️ Organizzazione',
'location' => '🌍 Luogo',
'event' => '📅 Evento',
'concept' => '💡 Concetto',
'topic' => '📚 Tema',
```

**Scope:**

- `published()` — solo pubblicate
- `byType($type)` — filtra per tipo
- `featured()` — ≥ 3 articoli collegati

**Accessor:**

- `type_label` → "👤 Persona"
- `type_color` → "blue"
- `status_label` → "Pubblicata"
- `url` → "/wiki/slug"
- `full_url` → "https://themedge.com/wiki/slug"
- `featured_image_url` → URL completo

**Helper:**

- `isPublished()` → bool

### WikiRevision

**Tabella:** `wiki_revisions`

Snapshot completo di ogni modifica:

| Campo | Descrizione |
|-------|-------------|
| `wiki_page_id` | FK pagina |
| `user_id` | FK utente |
| `title` | Titolo al momento |
| `summary` | Summary al momento |
| `content` | Content al momento |
| `infobox` | Infobox al momento |
| `change_summary` | "Aggiunta sezione biografia" |
| `is_minor` | Modifica minore |
| `is_ai_generated` | Generata da AI |

**Metodo:**

- `diffWithPrevious()` → array con diff

### Pivot

**`wiki_page_article`:**

- `wiki_page_id`, `article_id`
- `relevance` (0-100, calcolato)
- `context` (frase di contesto)

**`wiki_page_entity`:**

- `wiki_page_id`, `entity_id`
- `relation_type` (related, parent, child, mentioned)

---

## Auto-generazione

### WikiGenerator

**`app/Services/Wiki/WikiGenerator.php`**

### Flusso

```php
public function generateFromEntity(Entity $entity, bool $force = false): ?WikiPage
{
    // 1. Verifica esistenza
    $existing = WikiPage::where('entity_id', $entity->id)->first();
    if ($existing && !$force) return $existing;

    // 2. Trova articoli
    $articles = $this->findRelatedArticles($entity);
    if ($articles->isEmpty()) return null;

    // 3. Genera contenuto con Ollama
    $generated = $this->generateContent($entity, $articles);

    // 4. Crea o aggiorna WikiPage
    $data = [
        'title' => $entity->name,
        'slug' => $this->buildSlug($entity),
        'type' => $this->mapEntityTypeToWikiType($entity->type),
        'status' => 'draft',
        'summary' => $generated['summary'],
        'content' => $generated['content'],
        'infobox' => $generated['infobox'],
        'icon' => $this->getTypeIcon($entity->type),
        'featured_image' => $entity->photo_url,
        'is_ai_generated' => true,
        'ai_model' => config('services.ollama.model'),
        'ai_generated_at' => now(),
        'entity_id' => $entity->id,
        'articles_count' => $articles->count(),
    ];

    // 5. Salva e collega articoli
    $page = $existing ? tap($existing)->update($data) : WikiPage::create($data);
    $this->linkArticles($page, $articles, $entity);

    return $page;
}
```

### Prompt Ollama

```
Sei un redattore enciclopedico. Devi scrivere una voce di wiki per un giornale online italiano.

ENTITÀ: {name}
TIPO: {type}
BIO ESISTENTE: {bio}

ARTICOLI CHE NE PARLANO (estratti):
- [data] {titolo}: {excerpt}
- ...

Scrivi una voce wiki in italiano, stile enciclopedico ma giornalistico, con:
1. Un SOMMARIO di 1-2 frasi (summary)
2. Un CONTENUTO in markdown con sezioni pertinenti
3. Un INFOBOX con dati strutturati (JSON)

Rispondi SOLO con JSON valido:
{
  "summary": "...",
  "content": "## Sezione 1\n\nTesto...",
  "infobox": {"chiave1": "valore1"}
}

REGOLE:
- Non inventare fatti non presenti negli articoli
- Se un dato non è disponibile, ometti la chiave
- Tono neutro, enciclopedico
- Massimo 500 parole
```

### Job

**`App\Jobs\GenerateWikiPageFromEntity`**

- Queue: `default`
- Timeout: 300s
- Tries: 2
- Log: `GenerateWikiPageFromEntity started/completed/failed`

### Command

```bash
# Batch (async)
cd /www/wwwroot/themedge.com/api
php artisan wiki:generate --limit=30

# Sync (debug)
php artisan wiki:generate --limit=5 --sync

# Solo un tipo
php artisan wiki:generate --type=person --limit=10

# Singola entità
php artisan wiki:generate --entity=5 --sync

# Forza rigenerazione
php artisan wiki:generate --force
```

### Observer (auto)

`ArticleObserver::saved()`:

- Dispatch `GenerateArticleAudio` su publish
- **NON** dispatch wiki generation (manuale)

**Wiki auto-generation**: solo manuale da admin o command.

---

## Admin

**URL:** `/admin/wiki-pages`

### Lista

**Colonne:**

- Icona
- Titolo + subtitle
- Tipo (badge colorato)
- Stato (badge: draft/published/archived)
- Articoli (badge contatore)
- AI (icona spakles)
- Viste
- Aggiornata

**Filtri:**

- Tipo (6 opzioni)
- Stato (3 opzioni)
- Generata da AI
- Soft deleted

**Azioni per riga:**

- **Vedi sito** — apre `/wiki/slug`
- **Pubblica** — cambia stato a `published`
- **Rigenera AI** — dispatch job
- **Edit**
- **Delete**

**Bulk actions:**

- **Pubblica tutte**
- Delete/Restore/ForceDelete

### Header actions

- **Genera da entità** — form con tipo + limit
- **Nuova pagina**

### Form (Edit)

**Sezioni:**

1. **Identità**
   - Titolo (auto-slug)
   - Slug
   - Sottotitolo
   - Tipo (6 opzioni)
   - Stato (3 opzioni)
   - Icona (emoji)
   - Data pubblicazione

2. **Contenuto**
   - Sommario (textarea)
   - Content (RichEditor)
   - Infobox (KeyValue)
   - Alias (TagsInput)

3. **Media**
   - Featured image (FileUpload)
   - Didascalia

4. **SEO**
   - Meta title
   - Meta description

5. **Info AI & Sistema**
   - Generato da AI (placeholder)
   - Statistiche (placeholder)
   - Entità collegata (Select)

### Widget

- `WikiStatsWidget` (opzionale): pagine totali, pubblicate, bozze

---

## API

### GET /api/wiki

Lista pagine pubblicate con filtri.

**Query params:**

- `page` (default 1)
- `type` (person, organization, ...)
- `q` (ricerca title/summary)
- `per_page` (default 20, max 50)

**Response:**

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 3,
      "slug": "volodymyr-zelensky",
      "title": "Volodymyr Zelensky",
      "subtitle": "Persona",
      "type": "person",
      "summary": "...",
      "icon": "👤",
      "featured_image": null,
      "articles_count": 1,
      "views": 5,
      "published_at": "2026-10-14T13:34:40+00:00"
    }
  ],
  "total": 3,
  "per_page": 20
}
```

### GET /api/wiki/{slug}

Dettaglio pagina con articoli + entità correlate.

**Response:**

```json
{
  "data": {
    "id": 3,
    "slug": "volodymyr-zelensky",
    "title": "Volodymyr Zelensky",
    "type": "person",
    "type_label": "👤 Persona",
    "type_color": "blue",
    "summary": "...",
    "content": "## Biografia\n\n...",
    "infobox": {"Nome": "...", "Carica": "..."},
    "aliases": ["Zelensky"],
    "icon": "👤",
    "articles_count": 1,
    "views": 5,
    "is_ai_generated": true,
    "ai_model": "llama3.1:8b",
    "entity": {...},
    "articles": [
      {
        "id": 1,
        "title": "Il mio primo articolo",
        "slug": "il-mio-primo-articolo",
        "featured_image": "https://admin.themedge.com/storage/...",
        "category": {...},
        "relevance": 0
      }
    ],
    "related_entities": [...]
  }
}
```

### GET /api/wiki/featured

Wiki con ≥ 3 articoli collegati.

### GET /api/wiki/stats

```json
{
  "data": {
    "total": 3,
    "total_articles": 3,
    "by_type": {
      "person": 1,
      "location": 1,
      "organization": 1
    }
  }
}
```

### GET /api/wiki/search?q=...

Ricerca (Meilisearch fallback LIKE).

### GET /api/wiki/by-entity/{entitySlug}

Wiki collegata a una entità.

### GET /api/wiki/by-article/{articleSlug}

Wiki collegate a un articolo.

---

## Frontend

### Pagine

| URL | Descrizione |
|-----|-------------|
| `/wiki` | Home (hero, search, stats, featured, categorie, lista) |
| `/wiki/[slug]` | Dettaglio pagina |
| `/wiki/categoria/[type]` | Filtro per tipo |
| `/wiki/cerca` | Ricerca |
| `/wiki/grafo` | Knowledge Graph |

### Componenti

**`lib/wiki.ts`:**

- `getWikiPages()`, `getWikiPage()`, `getFeaturedWiki()`
- `getWikiStats()`, `searchWiki()`, `getWikiGraph()`
- Tipi: `WikiPageListItem`, `WikiPageDetail`, `WikiGraphData`
- Costanti: `WIKI_TYPE_LABELS`, `WIKI_TYPE_COLORS`

**`components/wiki/`:**

- `WikiCard.tsx` — card (3 varianti: default, compact, featured)
- `WikiContent.tsx` — markdown render
- `WikiInfobox.tsx` — infobox sidebar
- `WikiArticles.tsx` — grid articoli collegati
- `WikiEntities.tsx` — entità correlate
- `WikiSearch.tsx` — search con autocomplete
- `WikiGraph.tsx` — grafo force-directed
- `ShareWikiButtonClient.tsx` — share SSR-safe

### Pagina dettaglio

**Struttura:**

```
Breadcrumb → Home / Wiki / Tipo / Titolo
Hero (icona + tipo + titolo + subtitle + stats)
Summary (italic)
[Vedi entità] [Condividi]
Grid (2 col):
  Col 1:
    Featured image
    Content (markdown)
    AI disclosure (se generato)
  Col 2 (sticky):
    Infobox
    Entità correlate
Articoli collegati (grid)
CTA finale
```

**SEO JSON-LD:**

```json
{
  "@context": "https://schema.org",
  "@type": "DefinedTerm",
  "name": "Volodymyr Zelensky",
  "description": "...",
  "url": "https://themedge.com/wiki/volodymyr-zelensky",
  "inDefinedTermSet": {
    "@type": "DefinedTermSet",
    "name": "Themedge Wiki",
    "url": "https://themedge.com/wiki"
  }
}
```

---

## Knowledge Graph

**URL:** `/wiki/grafo`

**API:** `GET /api/wiki/graph?limit=150&with_articles=1`

**Response:**

```json
{
  "nodes": [
    {
      "id": "wiki-3",
      "kind": "wiki",
      "slug": "volodymyr-zelensky",
      "title": "Volodymyr Zelensky",
      "type": "person",
      "icon": "👤",
      "articles_count": 1,
      "size": 7
    },
    {
      "id": "article-1",
      "kind": "article",
      "slug": "il-mio-primo-articolo",
      "title": "Il mio primo articolo",
      "type": "article",
      "icon": "📰",
      "size": 4
    }
  ],
  "links": [
    {
      "source": "wiki-3",
      "target": "article-1",
      "kind": "article"
    }
  ],
  "stats": {
    "total_nodes": 7,
    "total_links": 5,
    "wiki_count": 4,
    "article_count": 3,
    "by_type": {"person": 1, "location": 1}
  }
}
```

**Features (`WikiGraph.tsx`):**

- Force-directed layout (`react-force-graph-2d`)
- Zoom + pan + drag
- Click nodo → apre wiki/articolo (nuova tab)
- Hover → tooltip con summary
- Filtro per tipo
- Toggle articoli on/off
- Search con evidenziazione
- Pulsante "🎯 Adatta alla vista"
- Legenda colori (7 colori)
- Node canvas custom (emoji + dimensione + colori)
- Link particles per relazioni wiki↔wiki

---

## Troubleshooting

### Wiki non generata

**Cause:**

1. Entità senza articoli
2. Ollama non disponibile
3. Testo troppo corto

**Fix:**

```bash
cd /www/wwwroot/themedge.com/api

# Verifica
cat > /tmp/check_ent.php << 'PHPEOF'
<?php
require __DIR__ . '/../../www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once __DIR__ . '/../../www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$e = App\Models\Entity::where('is_published', true)
    ->whereHas('articles', fn($q) => $q->where('status', 'published'))
    ->first();

echo "Entity: " . $e?->name . PHP_EOL;
echo "Articoli: " . ($e?->articles()->where('status', 'published')->count() ?? 0) . PHP_EOL;
PHPEOF

php /tmp/check_ent.php

# Test Ollama
curl http://localhost:11434/api/tags
```

### Generazione lenta (> 5 min)

**Causa:** modello grande o RAM insufficiente.

**Fix:**

```bash
free -h
ollama stop llama3.1:8b

# Usa qwen2.5:3b per wiki
sed -i 's/OLLAMA_MODEL=llama3.1:8b/OLLAMA_MODEL=qwen2.5:3b/' .env
php artisan config:clear
```

### Content markdown non renderizzato

**Causa:** `react-markdown` mancante.

**Fix:**

```bash
cd /www/wwwroot/themedge.com/web
npm install react-markdown remark-gfm
npm run build
pm2 restart themedge-web --update-env
```

### Immagini articoli non visibili

**Causa:** `featured_image` salvato come path relativo.

**Fix:** usa accessor `featured_image_url`:

```php
'featured_image' => $a->featured_image_url,  // ✅
'featured_image' => $a->featured_image,      // ❌
```

### 500 su `/admin/wiki-pages/{id}`

**Causa:** errore infolist.

**Fix:** rimuovi `ViewAction` o semplifica infolist:

```php
Tables\Actions\ViewAction::make(),  // rimuovi
```

---

## Roadmap

### Miglioramenti futuri

- [ ] **Versioning UI** (diff visuale tra revisioni)
- [ ] **Wiki correlate** (suggerimenti automatici)
- [ ] **Sitemap wiki** + RSS
- [ ] **TTS su ogni wiki**
- [ ] **Notifiche "nuova wiki su X"**
- [ ] **Wiki pubbliche** (contributi utenti moderati)
- [ ] **Import da Wikipedia** (per info mancanti)

---

## Riferimenti

- [Handoff](../HANDOFF.md) — Panoramica progetto
- [FEATURES](FEATURES.md) — Tutte le feature
- [RAG](RAG.md) — AI Chat + embedding
- [OLLAMA](OLLAMA.md) — Configurazione Ollama
- [Schema.org DefinedTerm](https://schema.org/DefinedTerm)

---

*Ultima modifica: 14 ottobre 2026*
