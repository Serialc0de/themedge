# 🚨 PHP-FPM — Regola Critica

## ⚠️ MAI USARE

systemctl restart php-fpm-84    → crash loop garantito
systemctl stop php-fpm-84       → crash loop
systemctl start php-fpm-84      → conflitto
pkill -9 -f php-fpm             → uccide anche lo script stesso

Motivo: il server ha php-fpm-84.service in crash loop quando si usa systemd. Il socket /tmp/php-cgi-84.sock rimane occupato, il servizio va in failed, e systemd tenta riavvii automatici in conflitto.

Stato attuale: php-fpm-84.service è MASKED in systemd.

---

## ✅ USA SEMPRE

/root/fpm.sh

Script in /root/fpm.sh:

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

Cosa fa:
1. Invia SIGQUIT al master (graceful shutdown)
2. Aspetta 2 secondi
3. Uccide eventuali processi residui (pattern specifico)
4. Rimuove il socket
5. Avvia PHP-FPM in daemon
6. Verifica che sia attivo

Uso:

/root/fpm.sh

# Verifica stato
ps aux | grep php-fpm | grep -v grep
ls -la /tmp/php-cgi-84.sock

---

## 🚑 Recovery da crash loop

Se PHP-FPM è in crash loop (socket occupato, sito in 502):

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

Atteso:

root      ... php-fpm: master process (/www/server/php/84/etc/php-fpm.conf)
www       ... php-fpm: pool www
www       ... php-fpm: pool www
srw------- 1 www www 0 ... /tmp/php-cgi-84.sock
HTTP/2 200

---

## 🔍 Diagnosi

Verifica stato:

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

Test rapido:

# Admin
curl -sI https://admin.themedge.com/admin/login | head -3

# API
curl -sI https://admin.themedge.com/api/articles | head -3

# Frontend
curl -sI https://themedge.com | head -3

Tutti devono dare HTTP/2 200 o 302.

---

## ⚙️ OPcache — Attenzione

Il server ha OPcache con:

opcache.validate_timestamps = Off
opcache.revalidate_freq = 0

Significato: PHP non ricarica i file modificati finché non riavvii PHP-FPM.

Implicazione: dopo ogni modifica a file PHP in produzione, serve /root/fpm.sh.

Workaround consigliato:

# Dopo ogni modifica PHP
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear
/root/fpm.sh

Per la cache Laravel non serve il restart PHP-FPM (usa file cache, non OPcache). Ma per le modifiche al codice PHP (models, controllers, resources, ecc.) sì.

---

## 🔧 Configurazione systemd

php-fpm-84.service è in /etc/systemd/system/ (o generato da aaPanel).

Stato attuale: MASKED

# Verifica
systemctl status php-fpm-84
# Atteso: masked (Reason: Unit php-fpm-84.service is masked.)

# Per smascherare (se mai servisse)
systemctl unmask php-fpm-84

# Per rimascherare
systemctl mask php-fpm-84

NON smascherare se non sai cosa stai facendo. Il mask previene crash loop.

---

## 🎯 Comandi operativi quotidiani

| Azione | Comando |
|---|---|
| Riavvio PHP-FPM | /root/fpm.sh |
| Verifica stato | ps aux \| grep php-fpm |
| Log errori | tail -50 /www/server/php/84/var/log/php-fpm.log |
| Test sito | curl -sI https://admin.themedge.com/admin/login |

---

## 📋 Note aggiuntive

Su aaPanel
NON usare mai la UI di aaPanel per restart PHP-FPM. Usa sempre /root/fpm.sh.

Su reboot
Dopo un riavvio del server, PHP-FPM non parte automaticamente (systemd è masked). Va avviato con:

/www/server/php/84/sbin/php-fpm --daemonize

Oppure puoi aggiungere un cron @reboot:

(crontab -l 2>/dev/null; echo "@reboot /www/server/php/84/sbin/php-fpm --daemonize") | crontab -

Pool workers
Configurazione attuale in php-fpm.conf:
- pm.max_children = 25
- pm.start_servers = 4
- pm.min_spare_servers = 3
- pm.max_spare_servers = 8
- pm.max_requests = 1000

Se il sito è lento, aumenta pm.max_children (dipende dalla RAM disponibile).
