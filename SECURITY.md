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
