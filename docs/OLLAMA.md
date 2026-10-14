# 🤖 Ollama — Guida completa

Configurazione, modelli, performance e troubleshooting di Ollama.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Panoramica](#panoramica)
2. [Installazione](#installazione)
3. [Modelli](#modelli)
4. [Configurazione](#configurazione)
5. [Performance](#performance)
6. [Gestione RAM](#gestione-ram)
7. [Integrazione Laravel](#integrazione-laravel)
8. [Feature AI Themedge](#feature-ai-themedge)
9. [Troubleshooting](#troubleshooting)

---

## Panoramica

**Ollama** è un runtime per modelli LLM locale. Su Themedge permette **8 feature AI** senza costi API e senza inviare dati a terze parti.

**Vantaggi:**

- ✅ **Zero costi** (nessun abbonamento)
- ✅ **Privacy** (dati mai escono dal server)
- ✅ **Offline** (funziona senza internet)
- ✅ **Controllo** (modelli open source)

**Svantaggi:**

- ❌ **Lento su CPU** (nessuna GPU sulla VPS)
- ❌ **RAM intensivo** (`llama3.1:8b` = 5.6 GB)
- ❌ **Qualità** inferiore a GPT-4/Gemini

---

## Installazione

### Linux (Ubuntu/Debian)

```bash
curl -fsSL https://ollama.com/install.sh | sh
```

### macOS

```bash
brew install ollama
```

### Verifica

```bash
ollama --version
systemctl status ollama
curl http://localhost:11434/api/tags
```

### Avvio manuale

```bash
ollama serve
```

---

## Modelli

### Modelli installati

| Modello | Dimensione | RAM | Uso su Themedge |
|---------|-----------|-----|-----------------|
| `llama3.1:8b` | 4.9 GB | 5.6 GB | TL;DR, Wiki, Fact-check |
| `qwen2.5:3b` | 1.9 GB | 2.2 GB | RAG, SEO, Entity |
| `nomic-embed-text` | 275 MB | 376 MB | Embedding RAG |
| `qwen2:0.5b` | 400 MB | ~600 MB | Fallback veloce |

### Download

```bash
# Modelli principali
ollama pull qwen2.5:3b
ollama pull nomic-embed-text

# Qualità top (se hai RAM)
ollama pull llama3.1:8b

# Fallback veloce
ollama pull qwen2:0.5b
```

### Lista modelli

```bash
ollama list
```

**Output:**

```
NAME                    ID              SIZE    MODIFIED
llama3.1:8b             46e0c10c039e    4.9 GB  2 days ago
qwen2.5:3b              357c53fb659c    1.9 GB  2 days ago
nomic-embed-text        xxx             275 MB  30 seconds ago
```

### Altri modelli utili

| Modello | Dimensione | Uso |
|---------|-----------|-----|
| `llama3.2:1b` | 1.3 GB | Alternativa veloce a qwen2.5:3b |
| `mxbai-embed-large` | 670 MB | Embedding di qualità superiore |
| `gemma2:2b` | 1.6 GB | Alternativa a qwen2.5 |
| `phi3:mini` | 2.3 GB | Alternativa leggera |
| `mistral:7b` | 4.1 GB | Alternativa a llama3.1 |
| `codellama:7b` | 3.8 GB | Per generazione codice |

### Rimozione modelli

```bash
ollama rm llama3.1:8b
```

---

## Configurazione

### Variabili d'ambiente

**Server Ollama:**

```bash
# /etc/systemd/system/ollama.service
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11434"
Environment="OLLAMA_KEEP_ALIVE=5m"
Environment="OLLAMA_NUM_PARALLEL=1"
Environment="OLLAMA_MAX_LOADED_MODELS=1"
Environment="OLLAMA_MODELS=/usr/share/ollama/.ollama/models"
```

**Ricarica:**

```bash
systemctl daemon-reload
systemctl restart ollama
```

**Parametri:**

| Variabile | Default | Descrizione |
|-----------|---------|-------------|
| `OLLAMA_HOST` | `127.0.0.1:11434` | Host e porta |
| `OLLAMA_KEEP_ALIVE` | `5m` | Tempo in RAM senza uso |
| `OLLAMA_NUM_PARALLEL` | `1` | Richieste parallele |
| `OLLAMA_MAX_LOADED_MODELS` | `1` | Modelli contemporanei in RAM |
| `OLLAMA_MODELS` | `~/.ollama/models` | Directory modelli |

### Themedge `.env`

```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
OLLAMA_EMBEDDING_TIMEOUT=60
FACTCHECK_AI_ENABLED=true
```

### `config/services.php`

```php
'ollama' => [
    'url' => env('OLLAMA_URL', 'http://localhost:11434'),
    'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
    'model_extraction' => env('OLLAMA_MODEL_EXTRACTION', 'qwen2.5:3b'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    'embedding_timeout' => (int) env('OLLAMA_EMBEDDING_TIMEOUT', 60),
    'factcheck_enabled' => (bool) env('FACTCHECK_AI_ENABLED', true),
    'timeout' => (int) env('OLLAMA_TIMEOUT', 300),
],
```

---

## Performance

### Benchmark su VPS (4 CPU, 7.75 GB RAM)

| Task | Modello | Tempo | Token/s |
|------|---------|-------|---------|
| TL;DR breve | llama3.1:8b | 152s | ~3 |
| TL;DR breve | qwen2.5:3b | 50s | ~10 |
| Wiki generation | llama3.1:8b | 131s | ~3 |
| RAG risposta | qwen2.5:3b | 80s | ~7 |
| Embedding | nomic-embed-text | <1s | — |

### Parametri di performance

**`num_predict`** (token max output):

```php
'options' => [
    'num_predict' => 200,  // Meno = più veloce
],
```

**`temperature`**:

```php
'options' => [
    'temperature' => 0.3,  // Meno = più deterministico
],
```

**`top_p`**:

```php
'options' => [
    'top_p' => 0.9,
],
```

### Ottimizzazioni

**1. Prompt corto:**

```php
// ❌ 5000 token → 8 min
$prompt = $tuttoIlContesto;

// ✅ 800 token → 1.5 min
$prompt = substr($contesto, 0, 500);
```

**2. Modello piccolo:**

```bash
# Se RAM < 4 GB liberi
sed -i 's/OLLAMA_MODEL=llama3.1:8b/OLLAMA_MODEL=qwen2.5:3b/' .env
```

**3. Streaming:**

Percepisce velocità anche se totale è lungo.

**4. Cache Redis:**

Query ripetute → risposta istantanea.

**5. Pre-generazione notturna:**

```bash
# Cron 3:00 — genera wiki per nuovi articoli
0 3 * * * cd /www/wwwroot/themedge.com/api && php artisan wiki:generate --limit=10
```

---

## Gestione RAM

### Modelli in RAM

```bash
ollama ps
```

**Output:**

```
NAME                    ID              SIZE      PROCESSOR    UNTIL
qwen2.5:3b              357c53fb659c    2.2 GB    100% CPU     4 minutes from now
nomic-embed-text        xxx             376 MB    100% CPU     4 minutes from now
```

### Ferma modello

```bash
ollama stop llama3.1:8b
```

### Auto-unload

Con `OLLAMA_KEEP_ALIVE=5m`, i modelli vengono scaricati dopo 5 min di inattività.

### Controllo RAM

```bash
free -h
```

**Atteso:**

```
              total        used        free
Mem:           7.8Gi       3.5Gi       1.7Gi
```

**Se `used` > 80%** → ferma modelli.

### Kill forzato

```bash
# Kill processi
pkill -9 -f "llama-server"

# Riavvia servizio
systemctl restart ollama
sleep 3

# Verifica
curl http://localhost:11434/api/tags
```

### Cron cleanup

```bash
# Ogni 30 min: ferma modelli inattivi
*/30 * * * * ollama ps | grep -E "Minutes|Hours" | awk '{print $1}' | xargs -I{} ollama stop {} 2>/dev/null
```

---

## Integrazione Laravel

### OllamaClient

**`app/Services/Llm/OllamaClient.php`:**

```php
class OllamaClient
{
    public function __construct()
    {
        $this->url = config('services.ollama.url');
        $this->model = config('services.ollama.model');
        $this->timeout = config('services.ollama.timeout', 300);
    }

    public function withModel(string $model): self
    {
        $clone = clone $this;
        $clone->model = $model;
        return $clone;
    }

    public function generate(string $prompt, array $options = []): ?string
    {
        $response = Http::timeout($this->timeout)
            ->post("{$this->url}/api/generate", [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
                'options' => array_merge([
                    'temperature' => 0.3,
                    'top_p' => 0.9,
                    'num_predict' => 1024,
                ], $options),
            ]);

        return $response->json('response');
    }

    public function isAvailable(): bool
    {
        try {
            return Http::timeout(5)->get("{$this->url}/api/tags")->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
```

### OllamaEmbeddingService

**`app/Services/Rag/OllamaEmbeddingService.php`:**

```php
public function embed(string $text): ?array
{
    $text = mb_substr($text, 0, 8000);

    $response = Http::timeout($this->timeout)
        ->post("{$this->url}/api/embeddings", [
            'model' => $this->model,
            'prompt' => $text,
        ]);

    return $response->json('embedding');
}
```

### Test dal terminale

```bash
# Test generazione
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5:3b",
  "prompt": "Ciao, chi sei?",
  "stream": false
}'

# Test embedding
curl http://localhost:11434/api/embeddings -d '{
  "model": "nomic-embed-text",
  "prompt": "test"
}'

# Test streaming
curl http://localhost:11434/api/generate -d '{
  "model": "qwen2.5:3b",
  "prompt": "Racconta una barzelletta",
  "stream": true
}'
```

---

## Feature AI Themedge

### 1. TL;DR Summary

**Modello:** `llama3.1:8b`
**Dove:** Admin `/admin/articles` → Action "✨ Genera TL;DR"

Genera:
- `tldr` (2-3 frasi)
- `key_points` (3-5 punti)
- `summary` (paragrafo)

### 2. Entity Detection

**Modello:** `qwen2.5:3b`
**Dove:** Admin `/admin/articles` → Action "🔍 Rileva entità"

Estrae entità (person, organization, location, event, concept).

### 3. SEO Optimizer

**Modello:** `qwen2.5:3b`
**Dove:** Admin `/admin/articles` → Action "🎯 Ottimizza SEO"

Genera:
- `seo_title` (max 60 char)
- `meta_description` (max 160 char)
- `slug_suggestion`
- `primary_keywords`
- `hashtags`

### 4. Fact-check Claim

**Modello:** `llama3.1:8b`
**Dove:** Admin `/admin/articles` → Action "✅ Fact-check automatico"

Estrae claim verificabili con verdict suggerito.

**Kill switch:** `FACTCHECK_AI_ENABLED=false`

### 5. Wiki Generation

**Modello:** `llama3.1:8b`
**Dove:** `/admin/wiki-pages` → Action "Genera da entità"

Genera pagina wiki completa (summary, content markdown, infobox).

### 6. Embedding (RAG)

**Modello:** `nomic-embed-text`
**Dove:** `php artisan rag:embed-all`

Genera embedding per articoli + wiki.

### 7. Chat RAG

**Modello:** `qwen2.5:3b`
**Dove:** `/chiedi`

Risponde a domande con citazioni verificabili.

### 8. TTS

**Modello:** Piper (non Ollama)
**Dove:** `/articolo/[slug]`

Genera audio MP3 con voce italiana.

---

## Troubleshooting

### Ollama lento (> 2 min per risposta)

**Diagnosi:**

```bash
free -h
ollama ps
```

**Se RAM > 80%:**

```bash
ollama stop llama3.1:8b
ollama stop qwen2.5:3b
```

**Se persiste:**

```bash
# Usa modello più piccolo
sed -i 's/OLLAMA_MODEL=qwen2.5:3b/OLLAMA_MODEL=qwen2:0.5b/' .env
cd /www/wwwroot/themedge.com/api
php artisan config:clear
```

### "Connection refused"

```bash
systemctl status ollama
systemctl restart ollama
ss -tlnp | grep 11434
```

### "Model not found"

```bash
ollama list
ollama pull qwen2.5:3b
```

### Modello bloccato in "Stopping..."

```bash
pkill -9 -f "llama-server"
systemctl restart ollama
```

### Risposta vuota

**Cause:**

1. Modello non risponde
2. Timeout
3. Prompt malformato

**Fix:**

```bash
# Test diretto
curl http://localhost:11434/api/generate -d '{"model":"qwen2.5:3b","prompt":"test","stream":false}'
```

### Errore JSON malformato

**Cause:** modello non rispetta `format: json`.

**Fix:** usa `preg_match` per estrarre JSON:

```php
if (!preg_match('/\{[\s\S]*\}/', $response, $matches)) {
    return null;
}
$data = json_decode($matches[0], true);
```

### RAM esaurita durante embedding

**Fix:**

```bash
# Ferma modelli non necessari
ollama stop llama3.1:8b

# Embedding usa solo nomic-embed-text (376 MB)
php artisan rag:embed-all --type=articles --sync
```

---

## Confronto modelli

### Generazione (test Themedge)

| Modello | Qualità | Velocità | RAM | Uso |
|---------|---------|----------|-----|-----|
| `llama3.1:8b` | ⭐⭐⭐⭐⭐ | ⭐⭐ | 5.6 GB | TL;DR, Wiki |
| `qwen2.5:3b` | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 2.2 GB | RAG, SEO |
| `qwen2:0.5b` | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 600 MB | Fallback |
| `gemma2:2b` | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 1.6 GB | Alternativa |
| `llama3.2:1b` | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 1.3 GB | Alternativa |

### Embedding

| Modello | Dim | Qualità | Velocità | RAM |
|---------|-----|---------|----------|-----|
| `nomic-embed-text` | 768 | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 376 MB |
| `mxbai-embed-large` | 1024 | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | 670 MB |
| `all-minilm` | 384 | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 50 MB |

---

## Sicurezza

### Isolamento

Ollama ascolta solo su `127.0.0.1:11434` (non esposto pubblicamente).

### Log

```bash
journalctl -u ollama -f
journalctl -u ollama -n 50 --no-pager
```

### Firewall

```bash
# Verifica che 11434 NON sia esposto
ss -tlnp | grep 11434
# Deve mostrare 127.0.0.1:11434, non 0.0.0.0:11434
```

---

## Aggiornamento

```bash
# Aggiorna Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Verifica
ollama --version
```

---

## Riferimenti

- [Ollama docs](https://github.com/ollama/ollama/tree/main/docs)
- [Ollama API](https://github.com/ollama/ollama/blob/main/docs/api.md)
- [Modelli disponibili](https://ollama.com/library)
- [Nomic Embed](https://blog.nomic.ai/posts/nomic-embed-text-v1)

---

*Ultima modifica: 14 ottobre 2026*
