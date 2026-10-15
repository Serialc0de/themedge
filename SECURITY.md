# 🔐 Security — Themedge

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Segnalazione vulnerabilità](#segnalazione-vulnerabilità)
2. [Vulnerabilità note](#vulnerabilità-note)
3. [Policy](#policy)
4. [Pratiche di sicurezza](#pratiche-di-sicurezza)

---

## Segnalazione vulnerabilità

**Email:** security@themedge.com

**NON aprire issue pubbliche** per vulnerabilità.

**Cosa includere:**

- Descrizione dettagliata
- Passi per riprodurre
- Impatto stimato
- Suggerimento fix (opzionale)
- Tuo nome/handle (per credito)

**Tempo di risposta:** 48h

**Divulgazione responsabile:** 90 giorni o fix disponibile, quello che viene prima.

---

## Vulnerabilità note

### braces@3.0.3 — CVE-2026-93687 (HIGH)

**Stato:** 🟡 Accettata (devDependency, non sfruttabile in produzione)

**Dettagli:**

- **CVE:** GHSA-vfj7-8cjw-p6xm
- **Severità:** High
- **Impattata:** `braces@3.0.3` (via `eslint-config-next` → `@next/eslint-plugin-next` → `fast-glob` → `micromatch` → `braces`)
- **Descrizione:** stack overflow in pattern profondamente annidati

**Analisi del rischio:**

- ✅ `braces` è usata **solo** in fase di lint (ESLint) — **mai** in produzione
- ✅ Il sito in produzione esegue `next start`, che non carica ESLint
- ✅ Lo sfruttamento richiede accesso al repo (iniezione di pattern nel codice)
- ✅ Nessun fix upstream disponibile (ultima versione: `3.0.3`)
- ❌ `npm audit fix --force` peggiorerebbe la situazione (downgrade a `eslint-config-next@14.2.35`)

**Decisione:**

Ignorata fino a quando `braces@>=3.0.4` non sarà disponibile. Rivalutare periodicamente con `npm audit`.

**Ultima verifica:** 14 ottobre 2026

---

### Tar (Next.js) — CVE-2025-64118 (MODERATE)

**Stato:** 🟡 Monitorata

**Dettagli:**

- **Impattata:** `tar` (dipendenza transitiva di `next`)
- **Descrizione:** path traversal in estrazione archivi
- **Impattata solo** durante `npm install` (build time)

**Analisi del rischio:**

- ✅ Non sfruttabile a runtime
- ✅ Richiede compromissione del registry npm
- ✅ Il build è isolato

**Decisione:** Aggiornare alla prossima release di Next.js.

**Ultima verifica:** 14 ottobre 2026

---

### Nessuna vulnerabilità critica attiva

**Ultimo audit completo:** 14 ottobre 2026

```bash
cd /www/wwwroot/themedge.com/web
npm audit

cd /www/wwwroot/themedge.com/api
composer audit
```

**Risultato:** 0 critical, 0 high sfruttabili in produzione.

---

## Policy

### Report di sicurezza

- **Email:** security@themedge.com
- **Tempo di risposta:** 48h
- **Divulgazione:** 90 giorni o fix disponibile

### Audit dipendenze

**Frontend (npm):**

```bash
cd /www/wwwroot/themedge.com/web
npm audit
npm audit --production  # solo dipendenze runtime
```

**Backend (composer):**

```bash
cd /www/wwwroot/themedge.com/api
composer audit
```

**CI/CD:**

- GitHub Actions esegue `npm audit --audit-level=high` su ogni PR
- Fallisce solo su `critical`
- Dependabot settimanale per patch automatiche

### Aggiornamenti

- **Dependabot:** settimanale
- **Manuale:** mensile (verifica changelog)
- **Major version:** trimestrale (test approfonditi)

---

## Pratiche di sicurezza

### 1. Sanitizzazione HTML

**Doppia difesa:**

1. **Input (Laravel):** `HtmlSanitizer` (symfony/html-sanitizer)
   - Allow-list configurabile
   - Blocco SSRF (iframe verso IP interni)
   - Blocco XSS (script, event handlers, javascript:)

2. **Output (Next.js):** `isomorphic-dompurify`
   - Allow-list tag/attributi
   - `FORBID_TAGS`: script, style, object, embed, form
   - `FORBID_ATTR`: onerror, onload, onclick, ecc.

### 2. Autenticazione

- **Admin:** Filament + Laravel Breeze (email + password)
- **Reader:** token anonimo device-based (localStorage)
- **API:** Sanctum (token-based)
- **Rate limiting:** 10 req/min su `/api/ask`, 30 req/min su `/api/ask/feedback`

### 3. SQL Injection

- **Eloquent ORM** con parametri bindati
- **Query raw:** `DB::select()` con binding
- **Nessun `->whereRaw()` con input utente**

### 4. XSS

- **Blade:** auto-escape `{{ }}`
- **React:** auto-escape JSX
- **`dangerouslySetInnerHTML`:** solo su HTML sanitizzato
- **CSP:** header configurato su Nginx (opzionale)

### 5. CSRF

- **Laravel:** token CSRF automatico su form
- **API:** Sanctum (token-based, no CSRF necessario)
- **Frontend:** `fetch` con `credentials: 'include'` dove serve

### 6. HTTPS

- **Cloudflare:** Full Strict
- **HSTS:** `max-age=15552000; includeSubDomains`
- **SSL:** Let's Encrypt wildcard A+
- **Force HTTPS:** redirect 301 su Nginx

### 7. File upload

- **Validation:** `mimes:jpg,jpeg,png,webp,gif,svg,pdf`
- **Max size:** 5 MB
- **Storage:** `storage/app/public` (non accessibile direttamente)
- **Symlink:** `public/storage` → `storage/app/public`

### 8. Secrets

- **NON committati:** `.env`, `.env.local`, chiavi API, token
- **Storage:** variabili d'ambiente server
- **Rotazione:** trimestrale (token GitHub, Cloudflare, ecc.)

### 9. Log

- **Nessun log** di password/token
- **Log rotati:** cleanup automatico (cron)
- **Accessibili:** solo da root

### 10. GDPR

- **Export dati:** `/api/reader/me/export`
- **Delete dati:** `DELETE /api/reader/me`
- **Cookie:** solo tecnici (no profilazione)
- **Privacy policy:** `/privacy`
- **Cookie banner:** informativo

### 11. Permessi file

- **Owner:** `www:www` (PHP-FPM + Nginx)
- **Directory:** 755
- **File:** 644
- **Storage:** 775 (scrittura)
- **.env:** 640
- **Symlink:** `public/storage`

### 12. Rate limiting

| Endpoint | Limite |
|----------|--------|
| `/api/oembed` | 60/min |
| `/api/ask` | 10/min |
| `/api/ask/feedback` | 30/min |
| `/api/highlights` | 120/min |
| Login | 5/min |
| Generale | 60/min |

### 13. CORS

- **API:** solo `themedge.com` e `admin.themedge.com`
- **Header:** `Access-Control-Allow-Origin`
- **Credentials:** abilitati su endpoint reader

### 14. SSRF

- **Iframe:** blocco IP interni/localhost
- **Webhook:** whitelist domini
- **URL resolve (oEmbed):** timeout 5s + whitelist provider

### 15. Backup

- **Frequenza:** giornaliera (3:00)
- **Cosa:** DB MySQL + storage + `.env`
- **Rotazione:** ultimi 7 giorni
- **Storage:** `/root/backups/themedge/`
- **Test ripristino:** mensile

### 16. Monitoring

- **PM2:** log + restart automatico
- **Cron:** check disk + cleanup
- **Cloudflare:** WAF + rate limiting
- **Laravel:** log errori + notifiche Filament

### 17. Dependency scanning

- **npm audit:** in CI + manuale
- **composer audit:** in CI + manuale
- **Dependabot:** settimanale
- **Trivy:** opzionale (container)

### 18. AI Security

- **Ollama:** ascolta solo su `127.0.0.1:11434` (non esposto pubblicamente)
- **Prompt injection:** sanitizzazione input utente
- **No PII** nei prompt (nessun dato utente inviato a Ollama)
- **Kill switch:** `FACTCHECK_AI_ENABLED=false`
- **Rate limiting:** 10/min su `/api/ask`

---

## Checklist sicurezza pre-deploy

- [ ] Nessun `.env` committato
- [ ] Nessuna chiave API in codice
- [ ] Sanitizzazione attiva su input utente
- [ ] CSRF token su form
- [ ] Rate limiting su endpoint pubblici
- [ ] Permessi file corretti (www:www, 755/644)
- [ ] HTTPS attivo + HSTS
- [ ] Backup recente disponibile
- [ ] Log senza dati sensibili
- [ ] Dipendenze aggiornate (no critical/high)

---

## Checklist sicurezza post-deploy

- [ ] `npm audit` → 0 critical
- [ ] `composer audit` → 0 critical
- [ ] Headers sicurezza attivi (HSTS, X-Frame-Options, ecc.)
- [ ] Login funziona + rate limiting attivo
- [ ] API pubblica raggiungibile + rate limit
- [ ] Admin protetto + 2FA (opzionale)
- [ ] Log PM2 + Laravel senza errori
- [ ] Cloudflare WAF attivo

---

## Header sicurezza (Nginx)

```nginx
add_header Strict-Transport-Security "max-age=15552000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

Verifica:

```bash
curl -sI https://themedge.com | grep -iE "strict-transport|x-frame|x-content|referrer|permissions"
```

---

## Incident response

### Se sospetti una compromissione

1. **Isola:** metti il sito in maintenance mode

```bash
cd /www/wwwroot/themedge.com/api
php artisan down
```

2. **Investiga:** controlla log

```bash
tail -200 storage/logs/laravel.log | grep -iE "error|exception|unauthorized"
tail -100 /www/wwwlogs/themedge.com.error.log
pm2 logs themedge-web --lines 100 --nostream
```

3. **Verifica file modificati:**

```bash
cd /www/wwwroot/themedge.com
find . -type f -newermt "1 day ago" -not -path "*/node_modules/*" -not -path "*/.next/*" -not -path "*/storage/*" 2>/dev/null | head -20
```

4. **Backup prima di modifiche:**

```bash
/root/backup-themedge.sh
```

5. **Ripristina se necessario:**

```bash
# Ripristina DB
gunzip < /root/backups/themedge/db_LATEST.sql.gz | mysql themedge_db

# Ripristina storage
tar xzf /root/backups/themedge/storage_LATEST.tar.gz -C /www/wwwroot/themedge.com/api/storage/app/public/
```

6. **Riporta online:**

```bash
php artisan up
```

7. **Comunica:** email a security@themedge.com

---

## Contatti

- **Security:** security@themedge.com
- **Admin:** admin@themedge.com
- **GitHub:** https://github.com/Serialc0de/themedge/security

---

*Ultima modifica: 14 ottobre 2026*

---

## 🔒 Sicurezza Ollama (15 ottobre 2026)

### Configurazione

Ollama ascolta **solo su localhost** (`127.0.0.1:11434`). Non è esposto a internet.

### Verifica

    netstat -tlnp | grep 11434
    # Atteso: 127.0.0.1:11434 (NON 0.0.0.0:11434)

    curl -s http://localhost:11434/api/tags | python3 -m json.tool

    # Dall'esterno (deve fallire):
    curl -s http://178.83.121.96:11434/api/tags
    # Atteso: connection refused / timeout

### Modelli usati

| Modello | Rischio | Note |
|---------|---------|------|
| qwen2.5:3b | Basso | Open source, ~1.9 GB |
| nomic-embed-text | Basso | Open source, ~275 MB |
| llama3.1:8b | Basso | Open source, ~4.9 GB |

**Tutti i modelli sono open source** (licenza permissiva). Nessun dato inviato a terzi.

### Privacy

- **Nessun dato utente inviato a OpenAI/Anthropic/Google**
- Tutte le elaborazioni AI avvengono sul VPS
- GDPR compliant by design
- Nessun tracciamento

### Rischio: prompt injection

Se un utente malizioso riesce a far eseguire comandi Ollama (es. via API admin compromessa), può:
- Consumare CPU/RAM
- Generare output enormi
- Saturare la coda

**Mitigazione:**
- Ollama non esposto pubblicamente
- Admin Filament protetto da login
- Rate limiting API
- Monitoraggio CPU/RAM (PM2)

### Rischio: modello compromesso

Se un modello scaricato da Ollama Hub fosse manomesso (improbabile ma possibile):
- Potrebbe generare output dannoso
- Potrebbe consumare risorse eccessive

**Mitigazione:**
- Usare solo modelli ufficiali Ollama (`ollama pull qwen2.5:3b`)
- Verificare hash (futuro)

### Comandi di sicurezza

    # Verifica ascolto
    netstat -tlnp | grep 11434

    # Verifica modelli in RAM
    ollama ps

    # Ferma modelli inattivi (cleanup)
    ollama stop llama3.1:8b

    # Controlla log
    journalctl -u ollama --since "1 hour ago"

---

## 🌐 Sicurezza Wikipedia/Commons

### Configurazione

Le API Wikipedia e Wikimedia Commons sono **pubbliche** e non richiedono autenticazione.

### Endpoint usati

- `https://it.wikipedia.org/w/api.php` (Wikipedia IT)
- `https://en.wikipedia.org/w/api.php` (Wikipedia EN)
- `https://commons.wikimedia.org/w/api.php` (Commons)

### User-Agent

**Sempre** inviare User-Agent nelle richieste:

    Http::withHeaders([
        'User-Agent' => 'Themedge/1.0 (https://themedge.com; bot@themedge.com)',
    ])->get(...);

**Motivo:** Wikipedia richiede User-Agent identificativo. Senza, rischio ban.

### Rate limiting (Wikipedia)

- **Max 200 richieste/secondo** (limite Wikimedia)
- Noi facciamo **1 richiesta per entity** → nessun rischio
- Job in coda, non paralleli

### Rischio: hotlinking immagini

**Evitato.** Le immagini vengono **scaricate** e servite dal nostro server, non hotlinkate.

### Rischio: contenuto offensivo

Wikipedia può contenere contenuto non adatto (raro). Se un'immagine è inappropriata:
- L'admin può sostituirla manualmente
- Oppure eliminarla dal campo `featured_image`

### Licenze immagini

Tutte le immagini hanno **licenza** (CC0, CC BY, CC BY-SA, Public Domain). Il sistema salva:
- `image_license` (es. "CC BY-SA 4.0")
- `image_author` (autore originale)
- `image_source_url` (link a Commons)

**Attribuzione automatica** sul frontend.

### Comandi di verifica

    # Test API Wikipedia
    curl -s "https://it.wikipedia.org/w/api.php?action=query&titles=Roma&prop=pageimages&format=json" | python3 -m json.tool | head -10

    # Test API Commons
    curl -s "https://commons.wikimedia.org/w/api.php?action=query&titles=File:Roma-Gonfalone.svg&prop=imageinfo&format=json" | python3 -m json.tool | head -10

---

## 🔑 Credenziali

### File sul server (NON versionati)

| Cosa | Path |
|------|------|
| MySQL | `/root/.my.cnf` |
| Redis | `/www/server/redis/redis.conf` |
| Meilisearch | `/root/.meilisearch_master_key` |
| GitHub token | `/root/.github-token` |
| Laravel APP_KEY | `/www/wwwroot/themedge.com/api/.env` |
| VAPID keys | `/www/wwwroot/themedge.com/api/.env` |
| Cloudflare token | `/www/wwwroot/themedge.com/api/.env` |
| Ollama | (nessuna auth, localhost) |

**NON committare MAI queste credenziali su GitHub.**

### Verifica `.gitignore`

    cd /www/wwwroot/themedge-repo
    grep -E "\.env|\.my\.cnf|\.github-token|storage" .gitignore

Atteso: `.env` presente (o `api/.env`).

---

## 🛡️ Sicurezza Admin

### Autenticazione

- **Filament Shield** con 4 ruoli (super_admin, editor, author, reader)
- 55 permessi granulari
- Login richiesto per tutte le route `/admin/*`
- Rate limiting login

### Protezione route sensibili

- `/admin/*` → login richiesto
- `/api/ask/*` → rate limit 10/min
- `/api/ask/feedback` → rate limit 30/min + anti-spam 5 min
- `/api/highlights/*` → rate limit 120/min
- `/api/oembed` → rate limit 60/min

### Audit log

Tutte le azioni admin sono loggate con **spatie/activitylog**:
- Chi (user_id)
- Cosa (event: created/updated/deleted)
- Quando (timestamp)
- Quali campi (logOnlyDirty)

Visibile in **Admin → Audit Log**.

### Impersonificazione

Admin può impersonare utenti (solo super_admin).

---

## 🔐 Sicurezza API

### Sanctum

- Token-based auth per API protette
- Token personali per integrazioni

### CORS

- Configurato in `config/cors.php`
- Solo domini autorizzati (`themedge.com`, `admin.themedge.com`)

### Rate limiting

- API pubbliche: 60 req/min per IP
- API admin: 120 req/min
- API AI (`/api/ask`): 10 req/min
- API feedback: 30 req/min + anti-spam

### HTTPS

- **Obbligatorio** (Cloudflare Full Strict)
- HSTS abilitato (`max-age=15552000; includeSubDomains`)
- Certificato wildcard `*.themedge.com`

---

## 🚨 Incident Response

### Sospetta violazione admin

    # 1. Verifica login recenti
    mysql -D themedge_db -e "
    SELECT user_id, ip_address, user_agent, created_at
    FROM sessions
    ORDER BY created_at DESC
    LIMIT 20
    "

    # 2. Verifica activity log
    mysql -D themedge_db -e "
    SELECT causer_id, description, created_at
    FROM activity_log
    WHERE created_at > NOW() - INTERVAL 24 HOUR
    ORDER BY created_at DESC
    LIMIT 50
    "

    # 3. Force logout tutti
    php artisan session:flush 2>/dev/null || mysql -D themedge_db -e "DELETE FROM sessions"

### Sospetta injection API

    # 1. Verifica log Laravel
    tail -100 /www/wwwroot/themedge.com/api/storage/logs/laravel.log | grep -iE "sql|injection|xss"

    # 2. Verifica rate limit
    # (log nginx)
    tail -100 /www/wwwlogs/themedge.com.log | grep -iE "429|403"

### Sospetto accesso Ollama

    # 1. Verifica connessioni a 11434
    netstat -tlnp | grep 11434
    ss -tlnp | grep 11434

    # 2. Se esposto, blocca subito
    iptables -A INPUT -p tcp --dport 11434 -j DROP

    # 3. Verifica processi
    ps aux | grep ollama | grep -v grep

---

## 📋 Checklist sicurezza

### Setup (già fatto)

- [x] Ollama solo su localhost
- [x] HTTPS obbligatorio (Cloudflare Full Strict)
- [x] HSTS abilitato
- [x] Sanctum per API
- [x] Filament Shield per admin
- [x] Activity log
- [x] Rate limiting API
- [x] CSP headers (via Cloudflare)
- [x] `.env` in `.gitignore`
- [x] Credenziali fuori dal repo
- [x] Backup automatico notturno
- [x] Logrotate configurato

### Da fare (futuro)

- [ ] 2FA per admin
- [ ] Rate limiting più aggressivo su `/api/ask`
- [ ] Rotazione token API
- [ ] Monitoring uptime (UptimeRobot)
- [ ] WAF personalizzato Cloudflare
- [ ] Penetration test

---

## 🔗 Vedi anche

- `HANDOFF.md` — Regole critiche
- `docs/OLLAMA.md` — Configurazione Ollama
- `docs/ADMIN_PANEL.md` — Guida admin
- `docs/TROUBLESHOOTING.md` — Problemi + fix
- `CHANGELOG.md` — Sezione [2026-10-15i]
