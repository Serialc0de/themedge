# 🚨 PHP-FPM — Regola Critica

**Ultimo aggiornamento:** 14 ottobre 2026

---

## ⚠️ MAI USARE

```bash
systemctl restart php-fpm-84    # → crash loop garantito
systemctl stop php-fpm-84       # → crash loop
systemctl start php-fpm-84      # → conflitto
pkill -9 -f php-fpm             # → uccide anche lo script stesso
```

**Motivo:** il server ha `php-fpm-84.service` in **crash loop** quando si usa `systemd`. Il socket `/tmp/php-cgi-84.sock` rimane occupato, il servizio va in `failed`, e systemd tenta riavvii automatici in conflitto.

**Stato attuale:** `php-fpm-84.service` è **MASKED** in systemd.

---

## ✅ USA SEMPRE

```bash
/root/fpm.sh
```

### Script `[FPM_SCRIPT]` (alias `/root/fpm.sh`)

```bash
#!/bin/bash
echo "Restarting PHP-FPM 8.4..."
pkill -QUIT -f "php-fpm: master" 2>/dev/null
sleep 2
pkill -9 -f "php-fpm: master" 2>/dev/null
pkill -9 -f "php-fpm: pool" 2>/dev/null
rm -f /tmp/php-cgi-84.sock
/www/server/php/84/sbin/php-fpm --daemonize
sleep 1

if ps aux | grep -q "[p]hp-fpm: master"; then
    echo "PHP-FPM avviato correttamente"
    ps aux | grep "[p]hp-fpm:" | head -3
else
    echo "PHP-FPM NON avviato"
    tail -20 /www/server/php/84/var/log/php-fpm.log
    exit 1
fi
```

### Cosa fa

1. Invia **SIGQUIT** al master (graceful shutdown)
2. Aspetta **2 secondi**
3. Uccide eventuali processi residui (pattern specifici)
4. Rimuove il **socket**
5. Avvia PHP-FPM in **daemon**
6. **Verifica** che sia attivo

### Uso

```bash
/root/fpm.sh

# Verifica stato
ps aux | grep php-fpm | grep -v grep
ls -la /tmp/php-cgi-84.sock
```

---

## 🚑 Recovery da crash loop

Se PHP-FPM è in **crash loop** (socket occupato, sito in 502):

```bash
# 1. Uccidi tutti i processi (pattern SPECIFICI)
pkill -QUIT -f "php-fpm: master" 2>/dev/null
sleep 2
pkill -9 -f "php-fpm: master" 2>/dev/null
pkill -9 -f "php-fpm: pool" 2>/dev/null

# 2. Rimuovi socket orfano
rm -f /tmp/php-cgi-84.sock

# 3. Avvia
/www/server/php/84/sbin/php-fpm --daemonize
sleep 2

# 4. Verifica
ps aux | grep php-fpm | grep -v grep
ls -la /tmp/php-cgi-84.sock
curl -sI https://admin.themedge.com/admin/login | head -3
```

**Atteso:**

```
root      ... php-fpm: master process (/www/server/php/84/etc/php-fpm.conf)
www       ... php-fpm: pool www
www       ... php-fpm: pool www
srw------- 1 www www 0 ... /tmp/php-cgi-84.sock
HTTP/2 200
```

---

## 🔍 Diagnosi

### Verifica stato

```bash
# Processi attivi
ps aux | grep php-fpm | grep -v grep

# Socket
ls -la /tmp/php-cgi-84.sock

# Config
cat /www/server/php/84/etc/php-fpm.conf | grep -E "^pid|^listen"

# Log
tail -50 /www/server/php/84/var/log/php-fpm.log

# Stato systemd (deve essere masked)
systemctl status php-fpm-84
```

### Test rapido

```bash
# Admin
curl -sI https://admin.themedge.com/admin/login | head -3

# API
curl -sI https://admin.themedge.com/api/articles | head -3

# Frontend
curl -sI https://themedge.com | head -3
```

Tutti devono dare **HTTP/2 200** o **302**.

---

## ⚙️ OPcache — Attenzione

Il server ha **OPcache** con:

```ini
opcache.validate_timestamps = Off
opcache.revalidate_freq = 0
```

**Significato:** PHP **non ricarica** i file modificati finché non riavvii PHP-FPM.

**Implicazione:** dopo ogni modifica a file PHP in produzione, serve `[FPM_SCRIPT]`.

**Workaround consigliato:**

```bash
# Dopo ogni modifica PHP
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
[FPM_SCRIPT]
```

**Nota:** per la cache Laravel **non serve** il restart PHP-FPM (usa file cache, non OPcache). Ma per le modifiche al **codice PHP** (models, controllers, resources, ecc.) **sì**.

---

## 🔧 Configurazione systemd

`php-fpm-84.service` è in `/etc/systemd/system/` (o generato da aaPanel).

**Stato attuale:** MASKED

```bash
# Verifica
systemctl status php-fpm-84
# Atteso: masked (Reason: Unit php-fpm-84.service is masked.)

# Per smascherare (se mai servisse)
systemctl unmask php-fpm-84

# Per rimascherare
systemctl mask php-fpm-84
```

**⚠️ NON smascherare** se non sai cosa stai facendo. Il mask previene crash loop.

---

## 🎯 Comandi operativi quotidiani

| Azione | Comando |
|--------|---------|
| **Riavvio PHP-FPM** | `[FPM_SCRIPT]` |
| **Verifica stato** | `ps aux \| grep php-fpm` |
| **Log errori** | `tail -50 /www/server/php/84/var/log/php-fpm.log` |
| **Test sito** | `curl -sI https://admin.themedge.com/admin/login` |

---

## 📋 Note aggiuntive

### Su aaPanel

**NON usare mai la UI di aaPanel** per restart PHP-FPM. Usa sempre `[FPM_SCRIPT]`.

### Su reboot

Dopo un riavvio del server, PHP-FPM **non parte automaticamente** (systemd è masked). Va avviato con:

```bash
/www/server/php/84/sbin/php-fpm --daemonize
```

Oppure puoi aggiungere un **cron `@reboot`**:

```bash
(crontab -l 2>/dev/null; echo "@reboot /www/server/php/84/sbin/php-fpm --daemonize") | crontab -
```

### Pool workers

Configurazione attuale in `php-fpm.conf`:

```ini
pm.max_children = 25
pm.start_servers = 4
pm.min_spare_servers = 3
pm.max_spare_servers = 8
pm.max_requests = 1000
```

Se il sito è **lento**, aumenta `pm.max_children` (dipende dalla RAM disponibile).

### ⚠️ Warning "executing too slow"

Se nei log vedi:

```
WARNING: [pool www] child XXX, script 'index.php' 
(request: "POST /index.php") executing too slow (37.97 sec), logging
```

**Non è un problema di PHP-FPM**, ma di uno script lento. Cause comuni:

- **Ollama** che elabora un prompt lungo (RAG, wiki generation)
- **Query MySQL** non ottimizzata
- **Loop infinito** in codice

**Diagnosi:**

```bash
# Log Laravel
tail -50 /www/wwwroot/themedge.com/api/storage/logs/laravel.log

# Log PM2
pm2 logs themedge-web --lines 50 --nostream

# Processi attivi
top -bn1 | head -20
```

**Se è Ollama:** normale (generazione AI può richiedere 1-2 minuti). Non è un errore.

**Se è altro:** investigare.

### Timeout PHP-FPM

Valori consigliati per Ollama + RAG:

```ini
; /www/server/php/84/etc/php-fpm.conf
request_terminate_timeout = 300s
```

**Nota:** il timeout effettivo è il **minore** tra `max_execution_time` (php.ini) e `request_terminate_timeout` (php-fpm.conf).

### Verifica configurazione

```bash
# PHP-FPM config
grep -E "request_terminate_timeout|pm.max_children" /www/server/php/84/etc/php-fpm.conf

# PHP max_execution_time
php -i | grep max_execution_time
```

---

## 🔗 Vedi anche

- [DEPLOY.md](DEPLOY.md) — Workflow deploy
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) — Problemi comuni
- [HANDOFF.md](../HANDOFF.md) — Regole critiche

---

*Ultima modifica: 14 ottobre 2026*

---

## 📝 Log e incidenti

⚠️ **Durante un incidente (loop, 500, errore ricorrente)**: **NON troncare**
`storage/logs/laravel.log` prima di aver capito la causa. Copia il file, poi tronca.

```bash
# ✅ SEMPRE
cp storage/logs/laravel.log /root/incident-$(date +%s).log
> storage/logs/laravel.log
```

**Motivo:** troncare un log mentre si sta ancora verificando un problema
distrugge le prove. Vedi `HANDOFF.md` regola #14.

**Logrotate** è configurato per `laravel.log` in `/etc/logrotate.d/themedge-laravel`
(rotazione giornaliera, 14 giorni di storico, compresso, `copytruncate` per
non far crashare il worker).


---

## 🔨 Script `/root/build-web.sh` (obbligatorio)

### Cosa fa

Build di Next.js con **ownership corretta** dopo il build.

### Perché serve

`npm run build` gira come **root**, ma PM2 serve `themedge-web` come utente **www**. Dopo la build, i file `.next/` sono di proprietà `root:root` → `www` non li può leggere → errore:

    Error: Could not find a production build in the '.next' directory.
    Try building your app with 'next build' before starting the production server.

### Soluzione: script `/root/build-web.sh`

    #!/bin/bash
    # Build di themedge-web con ownership corretta
    set -e
    cd /www/wwwroot/themedge.com/web
    export PATH=/www/server/nodejs/v22.23.3/bin:$PATH

    echo "🛑 Stop themedge-web..."
    pm2 stop themedge-web

    echo "🧹 Rimuovo .next..."
    rm -rf .next

    echo "🔨 Build in corso..."
    npm run build

    echo "🔐 Fix ownership..."
    chown -R www:www .next

    echo "🚀 Restart themedge-web..."
    pm2 restart themedge-web --update-env

    echo "✅ Done"
    pm2 list

### Uso

    /root/build-web.sh

### Verifica

    ls -la /www/wwwroot/themedge.com/web/.next/BUILD_ID
    # Atteso: owner www:www

---

## ⚠️ Regola: SEMPRE `chown -R www:www .next` dopo build

### Workflow corretto

    cd /www/wwwroot/themedge.com/web
    pm2 stop themedge-web
    export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
    rm -rf .next
    npm run build
    chown -R www:www .next        # ← OBBLIGATORIO
    pm2 restart themedge-web --update-env

### Perché

Senza `chown`, PM2 (utente `www`) non riesce a leggere `.next/` → errore "Could not find a production build" → crash loop.

---

## 📝 Log e incidenti

### Regola: MAI troncare log durante un incidente

    # ❌ MAI durante un debug attivo:
    > storage/logs/laravel.log

    # ✅ SEMPRE:
    cp storage/logs/laravel.log /root/incident-$(date +%s).log
    > storage/logs/laravel.log

### Motivo

Troncare un log mentre si sta ancora verificando un problema distrugge le prove. Copiare prima, poi troncare.

### Logrotate

Configurato in `/etc/logrotate.d/themedge-laravel`:

    /www/wwwroot/themedge.com/api/storage/logs/*.log {
        daily
        rotate 14
        missingok
        notifempty
        compress
        delaycompress
        copytruncate
        su www www
        create 0664 www www
    }

**`copytruncate`** evita di dover riavviare il worker (Laravel tiene il file aperto).

### Verifica logrotate

    logrotate -d /etc/logrotate.d/themedge-laravel 2>&1 | tail -20

---

## 🚨 Recovery: Next.js non parte

### Sintomo

- `pm2 logs themedge-web` mostra: `Could not find a production build in the '.next' directory`
- Pagina 500 su tutto il sito
- `pm2 list` mostra `themedge-web` come `online` ma crasha ad ogni richiesta

### Diagnosi

    cd /www/wwwroot/themedge.com/web
    ls -la .next/BUILD_ID
    # Se dice "No such file or directory" → .next manca o è corrotto
    ls -la .next/ | head -5
    # Se owner è root:root → serve chown

### Fix

**Se `.next` manca:**

    cd /www/wwwroot/themedge.com/web
    pm2 stop themedge-web
    export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
    npm run build
    chown -R www:www .next
    pm2 restart themedge-web --update-env

**Se `.next` è di root:**

    chown -R www:www /www/wwwroot/themedge.com/web/.next
    pm2 restart themedge-web

**Se ancora non va, rebuild pulito:**

    /root/build-web.sh

---

## 🚨 Recovery: PHP-FPM crash loop

### Sintomo

- Sito 502/504
- `systemctl status php-fpm-84` → masked
- Log PHP-FPM: `executing too slow` o crash

### Fix

    /etc/init.d/php-fpm-84 stop
    pkill -9 -f php-fpm
    rm -f /tmp/php-cgi-84.sock
    /www/server/php/84/sbin/php-fpm --daemonize
    sleep 2
    ps aux | grep php-fpm | grep -v grep

Oppure:

    /root/fpm.sh

---

## 🚨 Recovery: queue worker fermo

### Sintomo

- `pm2 list` mostra `themedge-queue` non `online`
- Job accumulati in coda

### Fix

    pm2 list | grep themedge-queue
    pm2 restart themedge-queue
    sleep 3
    pm2 logs themedge-queue --lines 10 --nostream
    php artisan queue:monitor default --max=100

---

## 🚨 Recovery: queue in loop (job che falliscono)

### Sintomo

- `failed_jobs` cresce rapidamente
- Log Laravel cresce di decine di MB in pochi minuti
- CPU al 100% senza traffico

### Fix

    pm2 stop themedge-queue

    cd /www/wwwroot/themedge.com/api
    php artisan queue:monitor default --max=100
    mysql -D themedge_db -e "SELECT COUNT(*) FROM jobs"
    mysql -D themedge_db -e "SELECT COUNT(*) FROM failed_jobs"

    # Se migliaia → TRUNCATE (dopo dump)
    cat > /tmp/dump_jobs.php << 'PHPEOF'
    <?php
    require '/www/wwwroot/themedge.com/api/vendor/autoload.php';
    $app = require_once '/www/wwwroot/themedge.com/api/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    use Illuminate\Support\Facades\DB;

    $file = '/root/jobs_dump_' . date('Ymd_His') . '.jsonl';
    $fh = fopen($file, 'w');
    foreach (DB::table('jobs')->cursor() as $row) fwrite($fh, json_encode($row) . "\n");
    fclose($fh);
    echo "Dump: $file\n";
    PHPEOF
    php /tmp/dump_jobs.php

    mysql -D themedge_db -e "TRUNCATE TABLE jobs"
    mysql -D themedge_db -e "TRUNCATE TABLE failed_jobs"

    pm2 restart themedge-queue
    php artisan queue:monitor default --max=100

Vedi `docs/TROUBLESHOOTING.md` → "Loop GenerateArticleAudio".

---

## 🚨 Recovery: RAM saturata

    ollama ps
    ollama stop llama3.1:8b
    ollama stop qwen2.5:3b
    free -h

    # Kill forzato se non risponde
    pkill -9 -f llama-server

---

## 🚨 Recovery: disco pieno

    df -h /

    # Pulizia backup
    find /www/wwwroot/themedge.com -name "*.bak*" -mtime +3 -delete

    # Pulizia log PM2
    pm2 flush

    # Tronca log Laravel (dopo backup)
    cp /www/wwwroot/themedge.com/api/storage/logs/laravel.log /root/incident-$(date +%s).log
    > /www/wwwroot/themedge.com/api/storage/logs/laravel.log

    # Pulizia journalctl
    journalctl --vacuum-time=7d

---

## 🚨 Recovery: errore permessi

    chown -R www:www /www/wwwroot/themedge.com/api
    chown -R www:www /www/wwwroot/themedge.com/web
    chmod -R 775 /www/wwwroot/themedge.com/api/storage
    chmod -R 775 /www/wwwroot/themedge.com/api/bootstrap/cache

Verifica:

    find /www/wwwroot/themedge.com/api -not -user www 2>/dev/null | wc -l
    find /www/wwwroot/themedge.com/web -not -user www 2>/dev/null | wc -l
    # Atteso: 0

---

## 📊 Configurazione PHP-FPM

### File di configurazione

    /www/server/php/84/etc/php-fpm.conf
    /www/server/php/84/etc/php-fpm.d/www.conf

### Socket

    ls -la /tmp/php-cgi-84.sock
    # Atteso: srw-rw---- 1 www www

### Pool workers

    ps aux | grep "php-fpm: pool" | wc -l
    # Atteso: 2-4

### OPcache

Verifica:

    php -i | grep opcache.validate_timestamps
    # Atteso: Off

Dopo ogni modifica PHP → `/root/fpm.sh`.

---

## 📋 Comandi operativi quotidiani

    # Riavvio PHP-FPM
    /root/fpm.sh

    # Rebuild Next.js (con ownership)
    /root/build-web.sh

    # Riavvio queue
    pm2 restart themedge-queue

    # Cache clear Laravel
    cd /www/wwwroot/themedge.com/api
    php artisan optimize:clear
    php artisan filament:optimize-clear

    # Stato sistema
    pm2 list
    df -h /
    free -h
    ollama ps

---

## 🔗 Vedi anche

- `HANDOFF.md` — Regole #1 (PHP-FPM), #2 (OPcache), #3 (Next.js), #14 (log)
- `docs/TROUBLESHOOTING.md` — Problemi + fix
- `docs/DEPLOY.md` — Workflow deploy + rollback
- `CHANGELOG.md` — Sezione [2026-10-15i]
