# 💬 RAG — Retrieval-Augmented Generation

Guida completa al Knowledge Engine di Themedge: Wiki + Graph + Chat AI.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Panoramica](#panoramica)
2. [Architettura](#architettura)
3. [Embedding](#embedding)
4. [Ricerca vettoriale](#ricerca-vettoriale)
5. [Prompt building](#prompt-building)
6. [Streaming SSE](#streaming-sse)
7. [Feedback & Analytics](#feedback--analytics)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)

---

## Panoramica

**Themedge Knowledge Engine** è un sistema RAG (Retrieval-Augmented Generation) completamente locale che permette agli utenti di fare domande e ricevere risposte **basate esclusivamente sui contenuti editoriali** di Themedge.

**Cosa fa:**

1. L'utente fa una domanda in italiano (`/chiedi`)
2. Il sistema **cerca** nei contenuti (articoli + wiki) i più rilevanti
3. Costruisce un **prompt** con i contenuti trovati
4. **Genera** una risposta con Ollama (streaming)
5. Cita le **fonti** con link cliccabili

**Vantaggi:**

- ✅ **100% locale** (Ollama, zero costi)
- ✅ **Citazioni verificabili** (`[1]`, `[2]` cliccabili)
- ✅ **Privacy** (nessun dato inviato a terze parti)
- ✅ **Streaming** (token-by-token come ChatGPT)
- ✅ **Feedback** utente (👍/👎) per migliorare

---

## Architettura

```
┌─────────────────────────────────────────────────────────┐
│  FRONTEND /chiedi                                       │
│  ──────────────                                         │
│  AskChat → POST /api/ask                                │
│           ← SSE (sources, token, done)                  │
└─────────────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│  BACKEND AskController                                  │
│  ────────────────────                                   │
│  1. RagSearchService::search()                          │
│     - OllamaEmbeddingService::embed(query)              │
│     - cosineSimilarity con embedding di ogni doc        │
│     - top K (2)                                         │
│  2. RagPromptBuilder::build(question, context)          │
│  3. Ollama stream (/api/generate)                       │
│  4. SendSse (sources, token, done)                      │
│  5. Salva AskQuery                                      │
└─────────────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────────────┐
│  MySQL JSON                                             │
│  ──────────                                             │
│  articles.embedding (JSON 768 dim)                      │
│  wiki_pages.embedding (JSON 768 dim)                    │
└─────────────────────────────────────────────────────────┘
```

---

## Embedding

### Modello

**`nomic-embed-text`** (768 dimensioni, ~275 MB).

```bash
ollama pull nomic-embed-text
```

### Generazione

**Manuale:**

```bash
cd /www/wwwroot/themedge.com/api

# Articoli + Wiki (sync)
php artisan rag:embed-all --type=both --sync

# Solo articoli
php artisan rag:embed-all --type=articles --sync

# Solo wiki
php artisan rag:embed-all --type=wiki --sync

# Forza rigenerazione
php artisan rag:embed-all --force
```

**Automatica:**

- Job `GenerateArticleEmbedding` (dispatch su `Article::saved`)
- Job `GenerateWikiEmbedding` (dispatch su `WikiPage::saved`)

### Storage

**Colonne JSON:**

```sql
ALTER TABLE articles ADD COLUMN embedding JSON NULL;
ALTER TABLE articles ADD COLUMN embedding_model VARCHAR(100) NULL;
ALTER TABLE articles ADD COLUMN embedding_generated_at TIMESTAMP NULL;

ALTER TABLE wiki_pages ADD COLUMN embedding JSON NULL;
ALTER TABLE wiki_pages ADD COLUMN embedding_model VARCHAR(100) NULL;
ALTER TABLE wiki_pages ADD COLUMN embedding_generated_at TIMESTAMP NULL;
```

**Esempio contenuto:**

```json
{
  "embedding": [0.4276, 1.5221, -2.8736, 0.9940, -0.0759, ...],
  "embedding_model": "nomic-embed-text",
  "embedding_generated_at": "2026-10-14 14:11:26"
}
```

### Testo embeddato

**Articoli:**

```php
$text = $article->title . "\n\n" . $article->excerpt . "\n\n" . strip_tags($article->content);
```

**Wiki:**

```php
$text = $page->title . "\n\n" . $page->subtitle . "\n\n" . $page->summary . "\n\n" . strip_tags($page->content);
```

**Troncato a 8000 caratteri** (limite modello).

---

## Ricerca vettoriale

### Algoritmo

**Cosine similarity** in PHP:

```php
public function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    for ($i = 0; $i < count($a); $i++) {
        $dot += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}
```

### Flusso

```php
// 1. Embed query
$queryEmbedding = $embedder->embed($question);

// 2. Per ogni articolo pubblicato
foreach ($articles as $article) {
    $score = $embedder->cosineSimilarity($queryEmbedding, $article->embedding);
    if ($score >= $minScore) {
        $results[] = ['...', 'score' => $score];
    }
}

// 3. Ordina per score decrescente
// 4. Prendi top K (default 2)
```

### Soglie

| Parametro | Valore | Note |
|-----------|--------|------|
| `minScore` | 0.4 | Score minimo per includere un risultato |
| `limit` | 2 | Numero massimo di fonti |
| Max doc | 50 | Limite hardcoded per performance |

### Esempio

**Query:** "Chi è Volodymyr Zelensky?"

**Risultati:**

```
[0.7939] 👤 Volodymyr Zelensky  → /wiki/volodymyr-zelensky
[0.5972] 🌍 Ucraina             → /wiki/ucraina
```

### Performance

| Documenti | Tempo |
|-----------|-------|
| 10 | ~50ms |
| 50 | ~200ms |
| 100 | ~400ms |
| 1000 | ~4s |

**Nota:** per > 500 documenti, considerare **Meilisearch vettoriale** o **Qdrant**.

---

## Prompt building

### Template

**Con fonti:**

```
Rispondi in italiano basandoti SOLO sulle fonti. Cita con [1], [2]. Se non ci sono info, dillo.

FONTI:
[1] Volodymyr Zelensky: Volodymyr Zelensky è un politico e attore ucraino, eletto Presidente dell'Ucraina nel 2019...

[2] Ucraina: La Ucraina è uno stato dell'Europa orientale, situato tra la Russia e la Polonia...

DOMANDA: Chi è Volodymyr Zelensky?

RISPOSTA:
```

**Senza fonti:**

```
Non ho informazioni su questo argomento negli archivi di Themedge. Rispondi brevemente che non puoi aiutare.

DOMANDA: Qual è il colore preferito di Zelensky?

RISPOSTA:
```

### Regole

1. **Ogni fonte** ha: `[N] Titolo: Contenuto (troncato a 500 char)`
2. **Numero fonti**: max 2
3. **Contesto max**: 500 char per fonte
4. **Istruzioni**: cita `[N]`, italiano, no invenzioni

### Perché così corto?

**CPU senza GPU** → ogni token di prompt richiede ~100ms. Con 5000 token → 8 minuti solo per il prompt. Con 800 token → ~1.5 minuti. **Compromesso.**

---

## Streaming SSE

### Server-Sent Events

**Formato:**

```
event: sources
data: {"sources":[...],"query_id":7}

event: token
data: {"text":"Volodymyr"}

event: token
data: {"text":" Zelensky"}

...

event: done
data: {"ok":true}
```

### Implementazione (backend)

```php
return response()->stream(function () use (...) {
    // 1. Invia fonti
    $this->sendSse('sources', [
        'sources' => $sources,
        'query_id' => $askQuery?->id,
    ]);

    // 2. Stream da Ollama via fopen
    $stream = fopen($ollamaUrl . '/api/generate', 'r', false, $ctx);

    while (!feof($stream)) {
        $line = fgets($stream);
        $json = json_decode($line, true);

        if (!empty($json['response'])) {
            $this->sendSse('token', ['text' => $json['response']]);
        }

        if (!empty($json['done'])) break;
    }

    fclose($stream);
    $this->sendSse('done', ['ok' => true]);
}, 200, [
    'Content-Type' => 'text/event-stream',
    'Cache-Control' => 'no-cache',
    'X-Accel-Buffering' => 'no',
]);
```

### Implementazione (frontend)

```typescript
const reader = response.body.getReader();
const decoder = new TextDecoder();
let buffer = '';

while (true) {
  const { done, value } = await reader.read();
  if (done) break;

  buffer += decoder.decode(value, { stream: true });

  let separatorIndex;
  while ((separatorIndex = buffer.indexOf('\n\n')) !== -1) {
    const rawEvent = buffer.slice(0, separatorIndex);
    buffer = buffer.slice(separatorIndex + 2);
    parseSseEvent(rawEvent, callbacks);
  }
}
```

### Note

- **`fopen` invece di Guzzle** — Guzzle ha problemi con streaming POST
- **`X-Accel-Buffering: no`** — disabilita buffering Nginx
- **`ob_implicit_flush(true)`** — flush immediato

---

## Feedback & Analytics

### Salvataggio query

**Best-effort** in `AskController::saveAskQuery()`:

```php
try {
    AskQuery::create([
        'question' => $question,
        'question_hash' => AskQuery::hashQuestion($question),
        'answer' => $fullAnswer,
        'answer_length' => mb_strlen($fullAnswer),
        'sources_count' => count($context),
        'sources' => [...],
        'model' => $model,
        'search_ms' => $searchMs,
        'total_ms' => $totalMs,
        'ip' => request()->ip(),
        'session_id' => $sessionId,
        'user_agent' => request()->userAgent(),
    ]);
} catch (\Throwable $e) {
    Log::warning('Failed to save AskQuery', ['error' => $e->getMessage()]);
}
```

### Feedback utente

**API:**

```
POST /api/ask/feedback
{
  "query_id": 7,
  "vote": 1,  // o -1
  "comment": "Risposta chiara"  // opzionale
}
```

**Rate limiting:**

- Max 30 feedback/minuto per IP
- Max 1 feedback per query ogni 5 minuti per IP

### Admin dashboard

`/admin/ask-queries`:

- **Widget:** query totali, % feedback positivi, tempo medio
- **Tabella:** domanda, fonti, feedback, tempo, IP, data
- **Filtri:** con feedback, con feedback negativo, ultime 24h, ultimi 7gg

### Metriche da monitorare

| Metrica | Target | Azione se fuori |
|---------|--------|-----------------|
| % feedback positivi | > 70% | Migliorare prompt |
| Tempo medio risposta | < 90s | Modello più piccolo |
| Fonti medie per risposta | 2 | OK |
| Query senza fonti | < 20% | Aggiungere contenuti |

---

## API Reference

### POST /api/ask

**Body:**

```json
{
  "question": "Chi è Volodymyr Zelensky?",
  "stream": true
}
```

**Response (SSE):**

```
event: sources
data: {"sources":[...],"query_id":7}

event: token
data: {"text":"Volodymyr"}

...

event: done
data: {"ok":true}
```

**Rate limit:** 10/min

**Response (non-streaming):**

```json
{
  "question": "Chi è Volodymyr Zelensky?",
  "answer": "Volodymyr Zelensky è un politico...",
  "sources": [...],
  "model": "qwen2.5:3b"
}
```

### POST /api/ask/feedback

**Body:**

```json
{
  "query_id": 7,
  "vote": 1,
  "comment": "Risposta chiara"
}
```

**Response:**

```json
{
  "ok": true,
  "message": "Grazie per il feedback positivo!",
  "feedback_id": 12
}
```

---

## Troubleshooting

### Risposta troppo lenta (> 2 min)

**Cause:**

1. RAM insufficiente → swap
2. Prompt troppo lungo
3. Modello troppo grande

**Fix:**

```bash
# Verifica
free -h
ollama ps

# Ferma modelli inutilizzati
ollama stop llama3.1:8b

# Usa modello più piccolo
sed -i 's/OLLAMA_MODEL=llama3.1:8b/OLLAMA_MODEL=qwen2.5:3b/' /www/wwwroot/themedge.com/api/.env
cd /www/wwwroot/themedge.com/api
php artisan config:clear
```

### Nessuna fonte trovata

**Cause:**

1. Embedding non generati
2. Query non correlata ai contenuti
3. `minScore` troppo alto

**Fix:**

```bash
# Verifica embedding
cat > /tmp/check_emb.php << 'PHPEOF'
<?php
require __DIR__ . '/../../www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once __DIR__ . '/../../www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Articoli con embedding: " . App\Models\Article::whereNotNull('embedding_generated_at')->count() . PHP_EOL;
echo "Wiki con embedding: " . App\Models\WikiPage::whereNotNull('embedding_generated_at')->count() . PHPEOF;

php /tmp/check_emb.php

# Rigenera
php artisan rag:embed-all --type=both --force
```

### SSE si interrompe

**Causa:** Cloudflare chiude connessioni > 100s (piano Free).

**Fix:**

- Se risposta > 100s → modello più piccolo
- O bypass Cloudflare con subdomain diretto

### Ollama error "connection refused"

```bash
systemctl status ollama
systemctl restart ollama
curl http://localhost:11434/api/tags
```

### "Missing field embedder"

**Causa:** vecchio tentativo con Meilisearch embedder.

**Fix:** MySQL JSON (già implementato).

---

## Roadmap

### Miglioramenti futuri

- [ ] **Cache Redis** (query ripetute → risposta istantanea)
- [ ] **Query expansion** (riformula domanda per migliori risultati)
- [ ] **Re-ranking** (LLM riordina fonti)
- [ ] **Hybrid search** (keyword + vettoriale)
- [ ] **Multi-hop** (domande complesse)
- [ ] **Suggerimenti automatici** (query correlate)
- [ ] **Podcast RSS** (dalle risposte)

### Modello più grande

Quando RAM/VPS lo permettono:

```bash
ollama pull llama3.1:70b
# 40 GB RAM richiesti
```

---

## Riferimenti

- [Ollama API](https://github.com/ollama/ollama/blob/main/docs/api.md)
- [Nomic Embed](https://blog.nomic.ai/posts/nomic-embed-text-v1)
- [RAG paper](https://arxiv.org/abs/2005.11401)
- [SSE spec](https://html.spec.whatwg.org/multipage/server-sent-events.html)

---

*Ultima modifica: 14 ottobre 2026*

---

## 🌐 Wikipedia RAG (15 ottobre 2026)

### Cosa fa

Genera wiki di alta qualità usando Wikipedia come fonte primaria, via RAG (Retrieval-Augmented Generation):

1. Scarica il testo Wikipedia dell'entità
2. Lo divide in chunk (2000 char, overlap 300)
3. Genera embedding con nomic-embed-text
4. Al momento della generazione wiki, recupera i 3 chunk più rilevanti
5. Li passa a Ollama nel prompt (arricchito con Wikipedia)

### Schema DB

**entities** (5 nuovi campi):

    wikipedia_url          string(500)
    wikipedia_text         longtext
    wikipedia_lang         string(5)
    wikipedia_fetched_at   timestamp
    wikipedia_status       string(20), default ''

**wikipedia_chunks** (nuova tabella):

    id, entity_id (FK), chunk_index, content (text), embedding (json 768), embedding_model (string), timestamps

### Stati wikipedia_status

| Stato | Significato |
|---|---|
| '' | Mai fetchata |
| fetching | Fetch in corso |
| ready | Fetch + chunk + embed completati |
| no_page | Wikipedia non ha pagina |
| skipped | Disambigua o vuota |
| no_content | Pagina trovata ma testo < 300 char |
| failed | Errore tecnico |

### Servizi

**WikipediaFetcherService**:

- fetchForEntity(Entity, force): orchestra fetch + chunk + embed
- searchWikipedia(name, aliases): cerca IT → EN
- fetchArticlePlainText(title, lang): API Wikipedia explaintext
- stripBoilerplate(text): rimuove Note/Bibliografia/Collegamenti finali
- chunkAndEmbed(entity): chunking + embedding, salva in wikipedia_chunks

**WikipediaRagService**:

- search(entity, query, limit, minScore): cosine similarity sui chunk
- buildContext(entity, query, limit): contesto pronto per il prompt
- stats(entity): statistiche copertura

### Job & Comando

**FetchWikipediaArticle** (Job, timeout 600s):

- Input: Entity, force
- Chiama WikipediaFetcherService
- Log dettagliato

**wiki:fetch-wikipedia** (Comando):

    php artisan wiki:fetch-wikipedia --limit=50
    php artisan wiki:fetch-wikipedia --entity=5 --sync
    php artisan wiki:fetch-wikipedia --force --status=ready

### Integrazione in WikiGenerator

WikiGenerator::buildWikipediaSection(Entity):

- Se wikipedia_status === 'ready', chiama WikipediaRagService::buildContext() (limit 3)
- Inserisce nel prompt: "WIKIPEDIA (fonte verificata, usa per i fatti principali)"
- Altrimenti: "WIKIPEDIA: non disponibile per questa entità"

### Risultati

- 21 entity con Wikipedia pronta (IT/EN)
- 573+ chunk con embedding
- 21 wiki generate con contenuti Wikipedia reali
- Content medio: ~900 char (vs ~300 senza Wikipedia)

### Admin UI

- **Admin → Entità**: colonna Wikipedia, filtro, action Fetch Wikipedia, bulk
- **Admin → Wiki**: colonna Wikipedia, action Fetch Wikipedia + Fetch + Rigenera

---

## 🖼️ Wikipedia Image Fetching (15 ottobre 2026)

### Cosa fa

Popola le wiki con immagini automatiche da Wikipedia/Wikimedia Commons, con attribuzione automatica.

### Schema DB

**wiki_pages** (6 nuovi campi):

    image_source_url     string(500)
    image_license        string(100)
    image_author         string(255)
    image_attribution    string(500)
    image_fetched_at     timestamp
    image_status         string(20), default ''

### Stati image_status

| Stato | Significato |
|---|---|
| '' | Mai fetchata |
| ready | Immagine trovata e scaricata |
| no_image | Pagina Wikipedia senza immagine principale |
| skipped | Wikipedia non ha pagina / disambigua |
| failed | Errore tecnico |

### Job & Comando

**FetchWikiImage** (Job, timeout 120s):

- Cerca su Wikipedia IT → EN
- Ottiene thumbnail 800px via API pageimages
- Ottiene metadati (autore, licenza) via Commons API
- Scarica in storage/app/public/wiki/

**wiki:fetch-images** (Comando):

    php artisan wiki:fetch-images --limit=30 --sync
    php artisan wiki:fetch-images --id=3 --sync --force

### Frontend

**WikiImageAttribution** (componente React):

- Varianti: overlay (card), caption (pagina dettaglio), inline
- Mostra: autore · licenza · via Wikimedia Commons · link fonte

### Risultati

- 19 wiki con immagine (su 30)
- 3.1 MB totali in storage
- Attribuzione automatica (autore + licenza + link Commons)

### Admin UI

- **Admin → Wiki**: colonna Immagine, filtro, action Fetch image, bulk Fetch images (batch)

---

## 🏷️ Entity Extraction auto-create (15 ottobre 2026)

### Cosa fa

Quando pubblichi un articolo, l'AI rileva le entità citate, le crea se non esistono, e le collega all'articolo.

### Flusso

1. Editore clicca Rileva entità (AI) su un articolo
2. Job DetectArticleEntities chiama EntityExtractor (Ollama qwen2.5:3b)
3. Estrae 5-15 entità (persone, luoghi, organizzazioni, eventi)
4. Crea in DB le entità mancanti (is_published=false, bozze)
5. Collega tutte le entità all'articolo via article_entity (syncWithoutDetaching)
6. Notifica Filament

### Modelli

**EntityExtractor**:

- Prompt semplificato con esempio multi-entità
- Dedup case-insensitive
- Regex JSON multiline
- Modello: qwen2.5:3b (rimosso llama3.1:8b per timeout Ollama 600s)

**DetectArticleEntities**:

- timeout = 1200 (20 min)
- tries = 1
- Ordine: solo qwen2.5:3b
- Auto-create + auto-attach

### Risultati

- 10 entità create in 98s da articolo ANSA
- Entità visibili in Admin → Entità come bozze
- 28 collegamenti article_entity totali

### Admin UI

- **Admin → Articoli**: action Rileva entità (AI)
- **Admin → Entità**: colonna Wikipedia, azioni per arricchire

### Pubblicazione entità

Le entità create dall'AI hanno is_published = false (bozze). Per pubblicarle:

1. Admin → Entità
2. Modifica l'entità → toggle "Pubblicata" → ON → Salva
3. Le entità pubblicate appaiono sul frontend come link

---

## 🔗 Entity ↔ Wiki collegamento (15 ottobre 2026)

### Cosa fa

Quando un articolo cita un'entità, il link preferisce la Wiki (se esiste) invece della pagina /entita/ vuota.

### Modelli

**Entity**:

- wikiPage(): HasOne, filtra solo published
- getUrlAttribute(): preferisce /wiki/slug se esiste wiki, altrimenti /entita/slug
- getWikiUrlAttribute(): null o /wiki/slug

**ArticleController**:

- Espone wiki_url nel JSON entità

### Effetto

Link "Giorgia Meloni" su articoli → /wiki/giorgia-meloni (con contenuto), non più /entita/giorgia-meloni (vuota).

---

## 📚 Riferimenti

- docs/RAG.md — Wikipedia RAG
- docs/TROUBLESHOOTING.md — Problemi Wikipedia + Ollama
- HANDOFF.md — Regole #15-19
- CHANGELOG.md — Sezione [2026-10-15i]
