# ⚙️ Setup — Themedge

Guida al setup completo del progetto in ambiente di sviluppo.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Prerequisiti](#prerequisiti)
2. [Setup rapido (Docker)](#setup-rapido-docker)
3. [Setup manuale (locale)](#setup-manuale-locale)
4. [Setup Ollama](#setup-ollama)
5. [Setup Piper TTS](#setup-piper-tts)
6. [Setup Meilisearch](#setup-meilisearch)
7. [Setup frontend](#setup-frontend)
8. [Variabili d'ambiente](#variabili-dambiente)
9. [Test post-setup](#test-post-setup)
10. [Comandi utili](#comandi-utili)

---

## Prerequisiti

### Software richiesto

| Software | Versione minima | Note |
|----------|-----------------|------|
| PHP | 8.3+ | con estensioni: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath` |
| Composer | 2.6+ | `curl -sS https://getcomposer.org/installer \| php` |
| Node.js | 20+ | consigliato 22 (LTS) |
| npm | 10+ | incluso con Node |
| MySQL | 8.0+ | o MariaDB 10.6+ |
| Redis | 7.0+ | cache + sessioni |
| Meilisearch | 1.6+ | ricerca full-text |
| Ollama | ultima | AI locale |
| Python | 3.10+ | per Piper TTS (via `pipx`) |
| ffmpeg | 6.0+ | conversione WAV → MP3 |

### Risorse hardware

**Minime:**
- 4 CPU
- 8 GB RAM
- 30 GB disco

**Consigliate:**
- 6+ CPU
- 16 GB RAM
- 60 GB disco

**⚠️ Nota RAM:** `llama3.1:8b` occupa 5.6 GB. Se la RAM totale è < 12 GB, usare `qwen2.5:3b` (2.2 GB).

### Sistemi operativi testati

- ✅ Ubuntu 22.04 / 24.04 / 26.04
- ✅ Debian 12
- ✅ macOS 13+
- ⚠️ Windows (via WSL2)

---

## Setup rapido (Docker)

**⚠️ Docker non è attualmente configurato.** Il progetto è deployato manualmente su VPS.

Se vuoi creare un `docker-compose.yml`:

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8
    environment:
      MYSQL_DATABASE: themedge_db
      MYSQL_ROOT_PASSWORD: secret
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  meilisearch:
    image: getmeili/meilisearch:v1.6
    environment:
      MEILI_MASTER_KEY: masterKey
      MEILI_ENV: development
    ports:
      - "7700:7700"
    volumes:
      - meili_data:/meili_data

  ollama:
    image: ollama/ollama:latest
    ports:
      - "11434:11434"
    volumes:
      - ollama_data:/root/.ollama

volumes:
  mysql_data:
  meili_data:
  ollama_data:
```

**Poi:**

```bash
docker-compose up -d
```

---

## Setup manuale (locale)

### 1. Clona il repository

```bash
git clone https://github.com/Serialc0de/themedge.git
cd themedge
```

### 2. Setup Backend (Laravel)

```bash
cd api

# Installa dipendenze
composer install

# Copia .env
cp .env.example .env

# Genera APP_KEY
php artisan key:generate

# Configura .env (vedi sezione Variabili d'ambiente)
nano .env

# Migration + seeder
php artisan migrate --seed

# Storage symlink
php artisan storage:link

# Crea admin
php artisan make:filament-user
```

### 3. Setup Database

**Crea DB:**

```bash
mysql -u root -p
```

```sql
CREATE DATABASE themedge_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'themedge'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON themedge_db.* TO 'themedge'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Configura `.env`:**

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=themedge_db
DB_USERNAME=themedge
DB_PASSWORD=password_sicura
```

### 4. Setup Redis

**Installa:**

```bash
# Ubuntu
sudo apt install redis-server

# macOS
brew install redis
```

**Configura `.env`:**

```bash
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 5. Avvio backend

```bash
cd api

# Sviluppo (con hot-reload)
php artisan serve

# Oppure in un processo separato:
php artisan queue:work
```

---

## Setup Ollama

### 1. Installa Ollama

**Linux:**

```bash
curl -fsSL https://ollama.com/install.sh | sh
```

**macOS:**

```bash
brew install ollama
```

**Verifica:**

```bash
ollama --version
curl http://localhost:11434/api/tags
```

### 2. Scarica modelli

```bash
# Modello per RAG (leggero)
ollama pull qwen2.5:3b

# Modello per generazione qualità (se hai RAM)
ollama pull llama3.1:8b

# Modello per embedding
ollama pull nomic-embed-text

# Modello velocissimo (fallback)
ollama pull qwen2:0.5b
```

### 3. Configura `.env`

```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
OLLAMA_EMBEDDING_TIMEOUT=60
FACTCHECK_AI_ENABLED=true
```

### 4. Test

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
```

### 5. Gestione RAM

```bash
# Modelli in RAM
ollama ps

# Ferma modello
ollama stop llama3.1:8b

# Lista disponibili
ollama list
```

---

## Setup Piper TTS

### 1. Installa pipx

**Ubuntu:**

```bash
sudo apt install -y pipx
pipx ensurepath

# Ricarica shell
source ~/.bashrc
```

**macOS:**

```bash
brew install pipx
pipx ensurepath
```

### 2. Installa Piper

```bash
pipx install piper-tts

# Verifica
piper --help
```

**⚠️ Nota Python 3.14:** se `pipx install piper-tts` fallisce, installa Python 3.12:

```bash
sudo apt install python3.12 python3.12-venv
python3.12 -m venv /opt/piper/venv
source /opt/piper/venv/bin/activate
pip install piper-tts
deactivate
```

### 3. Scarica modello italiano

```bash
mkdir -p /opt/piper/voices
cd /opt/piper/voices

BASE="https://huggingface.co/rhasspy/piper-voices/resolve/main/it/it_IT/paola/medium"

wget "$BASE/it_IT-paola-medium.onnx"
wget "$BASE/it_IT-paola-medium.onnx.json"

ls -la
```

### 4. Test

```bash
echo "Ciao, questo è un test." | piper \
  --model /opt/piper/voices/it_IT-paola-medium.onnx \
  --output_file /tmp/test.wav

file /tmp/test.wav
```

### 5. Configura `.env`

```bash
TTS_ENABLED=true
PIPER_BINARY=/root/.local/bin/piper
PIPER_MODEL=/opt/piper/voices/it_IT-paola-medium.onnx
PIPER_TIMEOUT=300

FFMPEG_BINARY=/usr/bin/ffmpeg
FFMPEG_BITRATE=128k
FFMPEG_SAMPLE_RATE=22050

TTS_STORAGE_DISK=public
TTS_STORAGE_PATH=audio
TTS_FORMAT=mp3

TTS_AUTO_GENERATE=true
TTS_AUTO_GENERATE_DELAY=30
TTS_AUTO_GENERATE_MIN_LENGTH=200
TTS_AUTO_GENERATE_MAX_LENGTH=50000
```

### 6. Installa ffmpeg

```bash
sudo apt install -y ffmpeg
ffmpeg -version
```

---

## Setup Meilisearch

### 1. Installa

**Linux:**

```bash
curl -L https://install.meilisearch.com | sh
sudo mv ./meilisearch /usr/local/bin/
```

**macOS:**

```bash
brew install meilisearch
```

### 2. Configura e avvia

```bash
# Crea directory dati
mkdir -p /var/lib/meilisearch/data.ms

# Genera master key
export MEILI_MASTER_KEY=$(openssl rand -hex 32)
echo "$MEILI_MASTER_KEY" > /root/.meilisearch_master_key

# Avvia
meilisearch \
  --db-path /var/lib/meilisearch/data.ms \
  --master-key "$MEILI_MASTER_KEY" \
  --http-addr 127.0.0.1:7700
```

**Oppure come servizio systemd:**

```bash
sudo tee /etc/systemd/system/meilisearch.service > /dev/null << 'EOF'
[Unit]
Description=Meilisearch
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/meilisearch --db-path /var/lib/meilisearch/data.ms --master-key YOUR_KEY --http-addr 127.0.0.1:7700
Restart=always

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable meilisearch
sudo systemctl start meilisearch
```

### 3. Configura `.env`

```bash
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=tua_master_key
```

### 4. Indicizza

```bash
cd api
php artisan scout:import "App\Models\Article"
php artisan scout:import "App\Models\WikiPage"
```

### 5. Test

```bash
curl -H "Authorization: Bearer $MEILI_MASTER_KEY" \
  http://localhost:7700/indexes

curl -X POST -H "Authorization: Bearer $MEILI_MASTER_KEY" \
  -H "Content-Type: application/json" \
  "http://localhost:7700/indexes/articles/search" \
  -d '{"q": "papa"}'
```

---

## Setup frontend

### 1. Installa dipendenze

```bash
cd web
npm install
```

### 2. Configura `.env.local`

```bash
nano .env.local
```

```bash
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

### 3. Avvio

```bash
# Sviluppo
npm run dev

# Build + start (produzione)
npm run build
npm start
```

### 4. Apri

```
http://localhost:3000
```

---

## Variabili d'ambiente

### Backend (`api/.env`)

**Generali:**

```bash
APP_NAME=Themedge
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=it
APP_FALLBACK_LOCALE=en
```

**Database:**

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=themedge_db
DB_USERNAME=themedge
DB_PASSWORD=password
```

**Cache/Session/Queue:**

```bash
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Redis:**

```bash
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

**Mail:**

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
MAIL_FROM_ADDRESS=hello@themedge.com
MAIL_FROM_NAME="Themedge"
```

**Meilisearch:**

```bash
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=master_key
```

**Ollama:**

```bash
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
OLLAMA_MODEL_EXTRACTION=qwen2.5:3b
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
OLLAMA_TIMEOUT=600
OLLAMA_EMBEDDING_TIMEOUT=60
FACTCHECK_AI_ENABLED=true
```

**TTS:**

```bash
TTS_ENABLED=true
PIPER_BINARY=/root/.local/bin/piper
PIPER_MODEL=/opt/piper/voices/it_IT-paola-medium.onnx
PIPER_TIMEOUT=300
FFMPEG_BINARY=/usr/bin/ffmpeg
TTS_STORAGE_DISK=public
TTS_STORAGE_PATH=audio
TTS_FORMAT=mp3
TTS_AUTO_GENERATE=true
TTS_AUTO_GENERATE_DELAY=30
TTS_AUTO_GENERATE_MIN_LENGTH=200
```

**Cloudflare:**

```bash
CLOUDFLARE_API_TOKEN=...
CLOUDFLARE_ZONE_ID=...
```

**VAPID (push):**

```bash
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:admin@themedge.com
```

### Frontend (`web/.env.local`)

```bash
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

---

## Test post-setup

### Backend

```bash
cd api

# Verifica connessione DB
php artisan db:show

# Verifica migration
php artisan migrate:status

# Test route
curl http://localhost:8000/api/articles

# Test admin
curl -I http://localhost:8000/admin
```

### Ollama

```bash
# Modelli installati
ollama list

# Test generazione
curl http://localhost:11434/api/generate -d '{"model":"qwen2.5:3b","prompt":"ciao","stream":false}'

# Test embedding
curl http://localhost:11434/api/embeddings -d '{"model":"nomic-embed-text","prompt":"test"}'
```

### Piper

```bash
echo "Ciao mondo" | piper --model /opt/piper/voices/it_IT-paola-medium.onnx --output_file /tmp/test.wav
ls -la /tmp/test.wav
```

### Meilisearch

```bash
curl http://localhost:7700/health
```

### Frontend

```bash
cd web
npm run build
npm start

# Test
curl -I http://localhost:3000
```

### End-to-end

```bash
# 1. Crea articolo
cd api
php artisan tinker
# In tinker:
# App\Models\Article::factory()->create();

# 2. Test API articoli
curl http://localhost:8000/api/articles

# 3. Test frontend
# Apri http://localhost:3000/articolo/slug

# 4. Test wiki
php artisan wiki:generate --limit=1 --sync

# 5. Test embedding
php artisan rag:embed-all --type=articles --sync

# 6. Test TTS
php artisan tts:test "Ciao mondo" --show
```

---

## Comandi utili

### Backend

```bash
# Cache clear
php artisan optimize:clear

# Filament cache
php artisan filament:optimize-clear

# Sync modelli Scout
php artisan scout:import "App\Models\Article"

# Queue worker (dev)
php artisan queue:work

# Queue worker (once)
php artisan queue:work --once

# Migration
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# Test (Pest)
php artisan test
```

### Frontend

```bash
# Dev
npm run dev

# Build
npm run build

# TypeScript check
npx tsc --noEmit

# Lint
npm run lint
```

### Ollama

```bash
ollama list              # lista modelli
ollama ps                # modelli in RAM
ollama pull <model>      # scarica
ollama rm <model>        # rimuovi
ollama stop <model>      # ferma
```

### Piper

```bash
echo "testo" | piper --model /opt/piper/voices/it_IT-paola-medium.onnx --output_file /tmp/out.wav
```

### Meilisearch

```bash
# Health
curl http://localhost:7700/health

# Indexes
curl -H "Authorization: Bearer $KEY" http://localhost:7700/indexes

# Stats
curl -H "Authorization: Bearer $KEY" http://localhost:7700/stats
```

### PM2 (produzione)

```bash
pm2 list
pm2 restart themedge-web --update-env
pm2 restart themedge-queue
pm2 logs themedge-web
pm2 logs themedge-queue
pm2 flush
```

### Redis

```bash
redis-cli
# In redis-cli:
# PING
# KEYS themedge:*
# FLUSHDB
```

---

## Problemi comuni

Vedi [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

---

## Prossimi passi

Dopo il setup:

1. **Leggi** [HANDOFF.md](../HANDOFF.md) per la panoramica
2. **Leggi** [FEATURES.md](FEATURES.md) per le feature
3. **Leggi** [DEPLOY.md](DEPLOY.md) per il deploy in produzione
4. **Leggi** [ADMIN_PANEL.md](ADMIN_PANEL.md) per il pannello admin

---

*Ultima modifica: 14 ottobre 2026*
