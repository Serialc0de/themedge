# 🚀 Deploy — Themedge

Guida completa al deploy e al rollback.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Panoramica](#panoramica)
2. [Regole d'oro](#regole-doro)
3. [Deploy Backend (Laravel)](#deploy-backend-laravel)
4. [Deploy Frontend (Next.js)](#deploy-frontend-nextjs)
5. [Deploy completo](#deploy-completo)
6. [Sync al repository](#sync-al-repository)
7. [Rollback](#rollback)
8. [Deploy feature specifiche](#deploy-feature-specifiche)
9. [Checklist pre-deploy](#checklist-pre-deploy)
10. [Checklist post-deploy](#checklist-post-deploy)

---

## Panoramica

**Stack di produzione:**

| Componente | Tecnologia | Porta |
|------------|-----------|-------|
| Backend | Laravel 13 + Filament 3 | PHP-FPM 8.4 |
| Frontend | Next.js 16 | 3000 |
| Web server | Nginx | 80/443 |
| Database | MySQL 8 | 3306 |
| Cache | Redis 7 | 6379 |
| Search | Meilisearch | 7700 |
| AI | Ollama | 11434 |
| Process Manager | PM2 | — |
| CDN/WAF | Cloudflare | — |

**Path produzione:**

```
/www/wwwroot/themedge.com/api/       ← Laravel
/www/wwwroot/themedge.com/web/       ← Next.js
/www/wwwroot/themedge-repo/          ← Repo Git (mirror)
```

**⚠️ IMPORTANTE:** il codice gira in `/www/wwwroot/themedge.com/`, **non** nel repo.

---

## Regole d'oro

### 1. PHP-FPM — MAI `systemctl`

Il servizio `php-fpm-84.service` è **masked**. Usare `[FPM_SCRIPT]`.

### 2. Next.js — SEMPRE `npm run build` prima di `pm2 restart`

Mai `pm2 restart themedge-web` da solo.

### 3. `pm2 stop` PRIMA della build critica

Evita di servire `.next` corrotta.

### 4. Modifica in PRODUZIONE, sync nel repo

Mai modificare direttamente `/www/wwwroot/themedge-repo/`.

### 5. Test sempre prima di commit

```bash
npx tsc --noEmit  # TypeScript
php -l file.php   # Sintassi PHP
```

### 6. Backup prima di modifiche grosse

```bash
cp file.php "file.php.bak-$(date +%s)"
```

---

## Deploy Backend (Laravel)

### Modifica singolo file

```bash
cd /www/wwwroot/themedge.com/api

# 1. Backup
cp app/Models/Article.php "app/Models/Article.php.bak-$(date +%s)"

# 2. Modifica (nano / cat >)
nano app/Models/Article.php

# 3. Verifica sintassi
php -l app/Models/Article.php

# 4. Fix owner (se modificato da root)
chown -R www:www app/

# 5. Cache clear
php artisan optimize:clear
php artisan filament:optimize-clear

# 6. Riavvio PHP-FPM
[FPM_SCRIPT]

# 7. Test
curl -I https://themedge.com
```

### Modifica migration

```bash
cd /www/wwwroot/themedge.com/api

# 1. Crea migration
php artisan make:migration nome_migration

# 2. Modifica il file
nano database/migrations/2026_XX_XX_HHMMSS_nome_migration.php

# 3. Verifica sintassi
php -l database/migrations/2026_XX_XX_HHMMSS_nome_migration.php

# 4. Migrate
php artisan migrate --force

# 5. Verifica
php artisan migrate:status | tail -5
```

### Modifica config

```bash
cd /www/wwwroot/themedge.com/api

# 1. Modifica
nano config/services.php

# 2. Verifica sintassi
php -l config/services.php

# 3. Clear config
php artisan config:clear
php artisan optimize:clear
```

### Modifica .env

```bash
cd /www/wwwroot/themedge.com/api

# 1. Backup
cp .env ".env.bak-$(date +%s)"

# 2. Modifica
nano .env

# 3. Clear config
php artisan config:clear
php artisan optimize:clear
```

### Nuova feature (service + job)

```bash
cd /www/wwwroot/themedge.com/api

# 1. Crea service
cat > app/Services/NomeService.php << 'PHPEOF'
<?php
namespace App\Services;
class NomeService
{
    //
}
PHPEOF

# 2. Verifica
php -l app/Services/NomeService.php

# 3. Fix owner
chown -R www:www app/Services/

# 4. Cache clear
php artisan optimize:clear
```

---

## Deploy Frontend (Next.js)

### Modifica singolo componente

```bash
cd /www/wwwroot/themedge.com/web

# 1. Backup
cp components/ArticleBody.tsx "components/ArticleBody.tsx.bak-$(date +%s)"

# 2. Modifica
nano components/ArticleBody.tsx

# 3. TypeScript check
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npx tsc --noEmit --project tsconfig.json 2>&1 | head -10

# 4. Build + deploy
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
sleep 3

# 5. Test
curl -I http://127.0.0.1:3000/
curl -I https://themedge.com

# 6. Purge Cloudflare
cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'
```

### Nuova pagina

```bash
cd /www/wwwroot/themedge.com/web

# 1. Crea directory (occhio a [param]! Usa quoting)
mkdir -p 'app/nuova-pagina'

# 2. Crea page.tsx
cat > app/nuova-pagina/page.tsx << 'TSXEOF'
export const metadata = {
  title: 'Nuova Pagina — Themedge',
};

export default function NuovaPagina() {
  return <div>Nuova pagina</div>;
}
TSXEOF

# 3. TypeScript check
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npx tsc --noEmit --project tsconfig.json 2>&1 | head -10

# 4. Build + deploy
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
```

### Nuova dipendenza npm

```bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

# 1. Installa
npm install nome-pacchetto

# 2. Verifica package.json
grep "nome-pacchetto" package.json

# 3. Build + deploy
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
```

---

## Deploy completo

**Deploy di TUTTO (backend + frontend):**

```bash
#!/bin/bash
set -e

echo "═══════════════════════════════════════════════════════"
echo "  DEPLOY COMPLETO THEMEDGE"
echo "═══════════════════════════════════════════════════════"

# ============================================================
# 1. BACKEND — Cache + PHP-FPM
# ============================================================
echo ""
echo "### 1. Backend..."

cd /www/wwwroot/themedge.com/api

php artisan optimize:clear
php artisan filament:optimize-clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Fix owner
chown -R www:www .

# Riavvio PHP-FPM
[FPM_SCRIPT]

echo "✅ Backend OK"

# ============================================================
# 2. FRONTEND — Build + Restart
# ============================================================
echo ""
echo "### 2. Frontend..."

cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

# TypeScript check
npx tsc --noEmit --project tsconfig.json 2>&1 | head -10

# Fix owner
chown -R www:www .

# Build
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
sleep 4

echo "✅ Frontend OK"

# ============================================================
# 3. QUEUE WORKER
# ============================================================
echo ""
echo "### 3. Queue worker..."
pm2 restart themedge-queue
sleep 2
echo "✅ Worker OK"

# ============================================================
# 4. PURGE CLOUDFLARE
# ============================================================
echo ""
echo "### 4. Purge Cloudflare..."
cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}' \
  -w "\nHTTP: %{http_code}\n"

# ============================================================
# 5. VERIFICA
# ============================================================
echo ""
echo "### 5. Verifica..."

pm2 list
curl -I http://127.0.0.1:3000/ 2>&1 | head -3
curl -I https://themedge.com 2>&1 | head -3
curl -I https://admin.themedge.com/admin 2>&1 | head -3

echo ""
echo "═══════════════════════════════════════════════════════"
echo "  ✅ DEPLOY COMPLETO"
echo "═══════════════════════════════════════════════════════"
```

---

## Sync al repository

**Dopo aver modificato in produzione, sync al repo:**

### Backend

```bash
cd /www/wwwroot/themedge-repo

# Sync API
rsync -av --delete --exclude='*.bak.*' --exclude='*.generated*' \
  /www/wwwroot/themedge.com/api/app/ api/app/

rsync -av /www/wwwroot/themedge.com/api/config/ api/config/
rsync -av /www/wwwroot/themedge.com/api/routes/ api/routes/
rsync -av /www/wwwroot/themedge.com/api/database/migrations/ api/database/migrations/
rsync -av /www/wwwroot/themedge.com/api/database/seeders/ api/database/seeders/
```

### Frontend

```bash
cd /www/wwwroot/themedge-repo

# Sync Web
rsync -av --delete --exclude='node_modules' --exclude='.next' \
  --exclude='*.bak*' --exclude='.env.local' \
  /www/wwwroot/themedge.com/web/app/ web/app/

rsync -av --delete --exclude='node_modules' --exclude='.next' \
  --exclude='*.bak*' \
  /www/wwwroot/themedge.com/web/components/ web/components/

rsync -av --delete --exclude='node_modules' --exclude='.next' \
  --exclude='*.bak*' \
  /www/wwwroot/themedge.com/web/lib/ web/lib/

# File root
cp /www/wwwroot/themedge.com/web/package.json web/package.json
cp /www/wwwroot/themedge.com/web/package-lock.json web/package-lock.json 2>/dev/null || true
cp /www/wwwroot/themedge.com/web/next.config.ts web/next.config.ts 2>/dev/null || true
cp /www/wwwroot/themedge.com/web/tsconfig.json web/tsconfig.json 2>/dev/null || true
```

### Commit + Push

```bash
cd /www/wwwroot/themedge-repo

# Verifica cosa verrà committato
git status --short

# Rimuovi .bak residui
find . -name "*.bak*" -not -path "./.git/*" -not -path "*/node_modules/*" -not -path "*/.next/*" -delete

# Commit
git add .
git commit -m "feat: descrizione breve

- Modifica 1
- Modifica 2"

# Push
git push origin main
```

---

## Rollback

### Rollback codice (git)

```bash
cd /www/wwwroot/themedge-repo

# 1. Vedi ultimi commit
git log --oneline -10

# 2. Rollback all'ultimo commit buono
git reset --hard <HASH_COMMIT_BUONO>

# 3. Push forzato (⚠️ attenzione)
git push origin main --force

# 4. Sul server, pull
cd /www/wwwroot/themedge.com/api
# Sync dal repo
```

### Rollback file singolo

```bash
cd /www/wwwroot/themedge.com/api

# Trova backup
ls -t app/Models/Article.php.bak* | head -1

# Ripristina
cp "$(ls -t app/Models/Article.php.bak* | head -1)" app/Models/Article.php

# Verifica
php -l app/Models/Article.php

# Cache clear + PHP-FPM
php artisan optimize:clear
[FPM_SCRIPT]
```

### Rollback frontend

```bash
cd /www/wwwroot/themedge.com/web

# Trova backup
ls -t components/ArticleBody.tsx.bak* | head -1

# Ripristina
cp "$(ls -t components/ArticleBody.tsx.bak* | head -1)" components/ArticleBody.tsx

# TypeScript check
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npx tsc --noEmit --project tsconfig.json 2>&1 | head -10

# Build + restart
pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
```

### Rollback database

```bash
cd /www/wwwroot/themedge.com/api

# Rollback ultima migration
php artisan migrate:rollback --step=1

# Rollback ultime 3 migration
php artisan migrate:rollback --step=3

# Rollback completo
php artisan migrate:reset
```

### Rollback storage (backup)

```bash
# Trova ultimo backup
ls -lh /root/backups/themedge/ | tail -5

# Ripristina DB
gunzip < /root/backups/themedge/db_YYYYMMDD_HHMMSS.sql.gz | mysql themedge_db

# Ripristina storage
tar xzf /root/backups/themedge/storage_YYYYMMDD_HHMMSS.tar.gz \
  -C /www/wwwroot/themedge.com/api/storage/app/public/
```

---

## Deploy feature specifiche

### Deploy Wiki

```bash
cd /www/wwwroot/themedge.com/api

# Migration
php artisan migrate --force

# Genera wiki da entità
php artisan wiki:generate --limit=30

# Verifica
php artisan tinker
# In tinker:
# App\Models\WikiPage::count();

[FPM_SCRIPT]
```

### Deploy RAG

```bash
cd /www/wwwroot/themedge.com/api

# Migration
php artisan migrate --force

# Genera embedding
php artisan rag:embed-all --type=both --sync

# Verifica
php artisan tinker
# In tinker:
# App\Models\Article::whereNotNull('embedding_generated_at')->count();

[FPM_SCRIPT]
```

### Deploy TTS

```bash
cd /www/wwwroot/themedge.com/api

# Migration
php artisan migrate --force

# Riavvia worker (per il nuovo observer)
pm2 restart themedge-queue

# Test
php artisan tts:test "Ciao mondo" --show

[FPM_SCRIPT]
```

---

## Checklist pre-deploy

- [ ] Backup file modificati (`.bak-TIMESTAMP`)
- [ ] TypeScript check passa (`npx tsc --noEmit`)
- [ ] PHP sintassi check passa (`php -l`)
- [ ] Test locale superato
- [ ] Migration testate in locale
- [ ] `.env` ha le variabili corrette
- [ ] RAM disponibile (`free -h` > 1 GB)
- [ ] Disco disponibile (`df -h /` > 3 GB)
- [ ] Nessun job critico in coda (`pm2 logs themedge-queue`)

---

## Checklist post-deploy

- [ ] `pm2 list` → tutti online
- [ ] `curl -I https://themedge.com` → 200
- [ ] `curl -I https://admin.themedge.com/admin` → 200
- [ ] `curl -I https://api.themedge.com/api/articles` → 200
- [ ] Login admin funziona
- [ ] Pagina modificata visibile
- [ ] React #418 assente (F12 → Console)
- [ ] Cloudflare purge eseguito
- [ ] Log Laravel senza errori
- [ ] Log PM2 senza errori

---

## Script deploy rapido

**Per modifiche frontend piccole:**

```bash
#!/bin/bash
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

npx tsc --noEmit --project tsconfig.json 2>&1 | head -5

pm2 stop themedge-web
rm -rf .next
npm run build
pm2 restart themedge-web --update-env
sleep 3

cd /www/wwwroot/themedge.com/api
ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

curl -I https://themedge.com
```

**Per modifiche backend piccole:**

```bash
#!/bin/bash
cd /www/wwwroot/themedge.com/api

php -l app/Models/Article.php
chown -R www:www app/
php artisan optimize:clear
php artisan filament:optimize-clear
[FPM_SCRIPT]

curl -I https://themedge.com
```

---

## Emergency rollback

**Se il sito è DOWN e non sai cosa hai rotto:**

```bash
#!/bin/bash
set -e

echo "🚨 EMERGENCY ROLLBACK"

# 1. Stop tutto
pm2 stop all

# 2. Ripristina ultimo backup DB
LATEST_DB=$(ls -t /root/backups/themedge/db_*.sql.gz | head -1)
echo "Ripristino: $LATEST_DB"
gunzip < "$LATEST_DB" | mysql themedge_db

# 3. Ripristina storage
LATEST_STORAGE=$(ls -t /root/backups/themedge/storage_*.tar.gz | head -1)
echo "Ripristino: $LATEST_STORAGE"
tar xzf "$LATEST_STORAGE" -C /www/wwwroot/themedge.com/api/storage/app/public/

# 4. Rollback Git all'ultimo commit noto buono
cd /www/wwwroot/themedge-repo
git log --oneline -10
# Scegli manualmente il commit buono
# git reset --hard <HASH_BUONO>
# git push origin main --force

# 5. Fix permessi
chown -R www:www /www/wwwroot/themedge.com/api
chown -R www:www /www/wwwroot/themedge.com/web

# 6. PHP-FPM
[FPM_SCRIPT]

# 7. Next.js rebuild
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
rm -rf .next
npm run build

# 8. Restart PM2
pm2 restart all
sleep 4

# 9. Verifica
curl -I https://themedge.com
pm2 list

echo "✅ ROLLBACK COMPLETATO"
```

---

## Note importanti

### ⚠️ MAI

- `npm run dev` in produzione
- `pm2 restart` senza `npm run build`
- Modificare direttamente `/www/wwwroot/themedge-repo/`
- Usare `systemctl` per PHP-FPM
- `sed` su file JSX/TSX
- `cp -r` senza verifica path duplicati
- `crontab -` con pipe

### ✅ SEMPRE

- Backup prima di modifiche
- TypeScript check prima di build
- `pm2 stop` prima di build critica
- `chown -R www:www` dopo modifiche root
- Purge Cloudflare dopo deploy
- Test browser dopo deploy
- Sync al repo dopo deploy

---

*Ultima modifica: 14 ottobre 2026*

---

## 🔨 Deploy Next.js (aggiornato 15 ottobre 2026)

### Workflow corretto

    cd /www/wwwroot/themedge.com/web
    export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

    # 1. Stop web
    pm2 stop themedge-web

    # 2. Clean build
    rm -rf .next
    npm run build

    # 3. Fix ownership (OBBLIGATORIO)
    chown -R www:www .next

    # 4. Restart
    pm2 restart themedge-web --update-env

    # 5. Verifica
    pm2 logs themedge-web --lines 10 --nostream

### Script `/root/build-web.sh`

Per evitare errori, usare lo script dedicato:

    /root/build-web.sh

Lo script fa tutto in un comando:
1. Stop web
2. rm -rf .next
3. npm run build
4. chown -R www:www .next
5. pm2 restart themedge-web --update-env
6. pm2 list

### Perché `chown` è obbligatorio

- `npm run build` gira come **root**
- PM2 serve `themedge-web` come **www**
- Senza `chown`, i file `.next/` sono `root:root` → `www` non li legge
- Errore: `Could not find a production build in the '.next' directory`

### Verifica

    ls -la /www/wwwroot/themedge.com/web/.next/BUILD_ID
    # Atteso: owner www:www

---

## 🚀 Deploy PHP (Laravel)

### Workflow corretto

    # 1. Modifica in produzione
    cd /www/wwwroot/themedge.com/api
    nano app/Services/...  # o altro

    # 2. Clear cache Laravel
    php artisan optimize:clear
    php artisan filament:optimize-clear

    # 3. Riavvio PHP-FPM (OPcache)
    /root/fpm.sh

    # 4. Riavvio queue worker
    pm2 restart themedge-queue

    # 5. Sync verso repo
    rsync -av /www/wwwroot/themedge.com/api/ /www/wwwroot/themedge-repo/api/ --exclude=storage --exclude=vendor --exclude=node_modules

    # 6. Commit
    cd /www/wwwroot/themedge-repo
    git add -A
    git commit -m "feat: descrizione"
    git push origin main

### Se modifichi migration

    cd /www/wwwroot/themedge.com/api
    php artisan migrate --force

### Se modifichi config

    php artisan config:clear
    php artisan optimize:clear

### Se modifichi solo la logica

    php artisan optimize:clear
    /root/fpm.sh

---

## 🗂️ Deploy con Git (sync repo → prod)

**Attenzione:** il workflow standard è **prod → repo** (per il documento HANDOFF regola #5). Ma in caso di rollback o pull:

### Pull dal repo a prod

    cd /www/wwwroot/themedge.com/api
    git pull origin main

    # Poi
    php artisan migrate --force
    php artisan optimize:clear
    /root/fpm.sh
    pm2 restart themedge-queue

**⚠️ Attenzione:** il sync prod → repo è **manuale** con `rsync`. Non esiste un webhook automatico.

---

## 🔄 Rollback

### Rollback PHP

    cd /www/wwwroot/themedge-repo
    git log --oneline -10
    git revert <commit-hash>

    # Poi sync verso prod
    rsync -av /www/wwwroot/themedge-repo/api/ /www/wwwroot/themedge.com/api/ --exclude=storage --exclude=vendor --exclude=node_modules

    cd /www/wwwroot/themedge.com/api
    php artisan optimize:clear
    /root/fpm.sh
    pm2 restart themedge-queue

### Rollback Next.js

    cd /www/wwwroot/themedge-repo
    git log --oneline -10
    git revert <commit-hash>

    rsync -av /www/wwwroot/themedge-repo/web/ /www/wwwroot/themedge.com/web/ --exclude=node_modules --exclude=.next

    /root/build-web.sh

### Rollback migration

    cd /www/wwwroot/themedge.com/api
    php artisan migrate:rollback --step=1
    php artisan optimize:clear
    /root/fpm.sh

**⚠️ Attenzione:** il rollback di migration può causare **perdita dati**. Fare backup prima:

    /root/backup-themedge.sh

---

## 💾 Backup prima del deploy

**Sempre** prima di un deploy critico:

    # Backup database + file
    /root/backup-themedge.sh

    # Verifica
    ls -lh /root/backups/themedge/

### Cosa contiene il backup

- Database MySQL (`themedge_db`)
- File API (`/www/wwwroot/themedge.com/api/`)
- File Web (`/www/wwwroot/themedge.com/web/`)
- Config (`.env`, nginx, php-fpm)
- Retention: 7 giorni

---

## 📊 Deploy checklist

### Prima del deploy

- [ ] `git status` pulito (o commit pendenti noti)
- [ ] Backup eseguito (`/root/backup-themedge.sh`)
- [ ] `pm2 list` mostra tutto online
- [ ] `php artisan queue:monitor default` → `[0] OK`
- [ ] Test in locale o staging (se possibile)

### Durante il deploy

- [ ] Modifica in `/www/wwwroot/themedge.com/`
- [ ] `php artisan optimize:clear`
- [ ] `/root/fpm.sh` (PHP)
- [ ] `/root/build-web.sh` (Next.js)
- [ ] `pm2 restart themedge-queue`

### Dopo il deploy

- [ ] `curl -I https://themedge.com` → 200
- [ ] `curl -I https://admin.themedge.com/admin` → 200/302
- [ ] `curl -I https://api.themedge.com/api/articles` → 200
- [ ] `pm2 logs themedge-web --lines 20 --nostream` → nessun errore
- [ ] `tail -30 storage/logs/laravel.log` → nessun errore
- [ ] `php artisan queue:monitor default` → `[0] OK`
- [ ] Purge Cloudflare (se necessario)

---

## 🌐 Purge Cloudflare

Dopo il deploy, se le modifiche non si vedono subito:

    cd /www/wwwroot/themedge.com/api
    ZONE=$(grep '^CLOUDFLARE_ZONE_ID=' .env | cut -d= -f2)
    TOKEN=$(grep '^CLOUDFLARE_API_TOKEN=' .env | cut -d= -f2)
    curl -sS -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE}/purge_cache" \
      -H "Authorization: Bearer ${TOKEN}" \
      -H "Content-Type: application/json" \
      --data '{"purge_everything":true}' \
      -w "\nHTTP: %{http_code}\n"

### Purge selettivo (fatto automaticamente)

`Article::purgeCloudflareCache()` viene chiamato in `saved()`:
- Purge dell'articolo + homepage + categoria + autore
- Solo per articoli pubblicati
- Log warning se fallisce

---

## 🔗 Vedi anche

- `HANDOFF.md` — Regole #3 (Next.js), #5 (prod → repo)
- `docs/PHP_FPM.md` — Script build-web.sh + chown .next
- `docs/TROUBLESHOOTING.md` — Problemi + fix
- `CHANGELOG.md` — Sezione [2026-10-15i]
