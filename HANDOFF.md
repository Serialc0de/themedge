# 🤝 Handoff — Progetto Themedge

Data ultima modifica: 12 ottobre 2026
Ore totali: ~50
Stato: ~98% completo
Ultimo commit: 2042f21

---

## 🚨 Regola PHP-FPM

MAI usare `systemctl restart php-fpm-84` → crash loop garantito.

SEMPRE usare:

    /root/fpm.sh

Vedi docs/PHP_FPM.md per dettagli.

---

## 📊 Stato del progetto

### ✅ Implementato

Sistema Embed
- 8 provider (YouTube, Vimeo, Spotify, Twitter, Instagram, TikTok, Generic)
- Sanitizzazione HTML + oEmbed
- Rate limiting

Sistema Highlight (Livello 3 NYT-level)
- Selezione testo + popup
- 3 colori + note + reazioni immutabili
- SVG overlay (non tocca HTML)
- Fuzzy match W3C-style
- Highlight orfani
- Heatmap articolo + top fragments
- Condivisione come immagine PNG
- Admin moderazione + ban reader
- Policy a 3 livelli (articolo, categoria, autore)
- Override JSON granulari
- Scadenza temporale

Pacchetto Credibilità
- Correzioni pubbliche inline (badge + diff)
- Fonti verificabili (8 tipi, API, admin repeater)
- Approfondimenti curati (many-to-many con note)

Pacchetto Lettore Premium
- Reading List (Salva per dopo)
- Text-to-Speech (Web Speech API)
- Share nativi (Web Share API + social)

Admin Panel Filament (30+ feature)
- 16 Filament Resource
- 6 Filament Page
- 15 Widget Dashboard
- UserResource + Shield (55 permessi, 4 ruoli)
- ArticleResource + workflow editoriale
- Editor SEO (9 check + anteprima Google)
- Media Library
- Audit Log (spatie/activitylog v5)
- Redirect Manager
- SEO Toolkit (settings, robots, sitemap)
- Breaking News System
- Live Blog
- Push Notifications PWA
- Commenti con moderazione
- Analytics Editoriale
- Highlight Resource + Policy

Frontend Next.js (20+ pagine)
- Homepage, articolo, categoria, tag, autore
- Ricerca Meilisearch
- Reading list /salvati
- Highlight personale /highlight
- GDPR /privacy/dati-personali
- Redazione, correzioni, segnala
- Ultimora, dirette, diretta/[slug]
- Chi siamo, contatti, privacy
- RSS, sitemap, robots
- 404 personalizzata

Sicurezza
- Doppia sanitizzazione (input + output)
- Blocco SSRF/XSS
- Force HTTPS
- Rate limiting
- GDPR (export/delete)
- Cloudflare WAF + HSTS
- SSL wildcard A+

Performance
- Cloudflare Cache Rule HTML
- Purge automatico on update
- TTFB 30-80ms (-90%)
- Fluid typography

Responsive
- Mobile, tablet, desktop 100%
- Hamburger menu (fino a xl)
- Tap target 44px ovunque

### ⏳ Da implementare (~2%)

Sessione 6 — Account + Cross-device (OAuth Google): 3-4h
Sessione 7 — Paywall / Stripe: 6-8h
Sessione 8 — Newsletter + Notifiche email: 5-6h
Sessione 9 — Timeline, Glossario, Fact-check: 4-5h
Sessione 10 — Font size control, Reading mode, Top 5: 4h

---

## 📁 Workflow modifica

IMPORTANTE: il codice gira in /www/wwwroot/themedge.com/api e /web, NON nel repo /www/wwwroot/themedge-repo/.

### Modifica + Deploy Backend PHP

1. cd /www/wwwroot/themedge.com/api
2. Modifica file
3. chown -R www:www app/
4. php -l file.php
5. php artisan optimize:clear
6. php artisan filament:optimize-clear
7. /root/fpm.sh

### Modifica + Deploy Frontend Next.js

1. cd /www/wwwroot/themedge.com/web
2. Modifica file
3. export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
4. npx tsc --noEmit --project tsconfig.json
5. npm run build
6. pm2 restart themedge-web --update-env

### Sync al repo — API

cd /www/wwwroot/themedge-repo

rsync -av --delete --exclude='*.bak.*' --exclude='*.generated*' /www/wwwroot/themedge.com/api/app/ api/app/

rsync -av /www/wwwroot/themedge.com/api/config/ api/config/
rsync -av /www/wwwroot/themedge.com/api/routes/ api/routes/
rsync -av /www/wwwroot/themedge.com/api/database/migrations/ api/database/migrations/
rsync -av /www/wwwroot/themedge.com/api/database/seeders/ api/database/seeders/

### Sync al repo — Web

rsync -av --delete --exclude='node_modules' --exclude='.next' --exclude='*.bak*' --exclude='.env.local' /www/wwwroot/themedge.com/web/app/ web/app/

rsync -av --delete --exclude='node_modules' --exclude='.next' --exclude='*.bak*' /www/wwwroot/themedge.com/web/components/ web/components/

rsync -av /www/wwwroot/themedge.com/web/lib/ web/lib/

### Commit

cd /www/wwwroot/themedge-repo
git add .
git status --short
git commit -m "feat: descrizione"
git push origin main

---

## 🔧 Comandi Utili

Riavvio PHP-FPM: /root/fpm.sh

Cache Laravel + Filament:

    cd /www/wwwroot/themedge.com/api
    php artisan optimize:clear
    php artisan filament:optimize-clear

Rebuild frontend:

    cd /www/wwwroot/themedge.com/web
    export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
    npm run build
    pm2 restart themedge-web --update-env

Log:

    tail -f /www/wwwroot/themedge.com/api/storage/logs/laravel.log
    tail -f /www/wwwlogs/admin.themedge.com.error.log
    tail -f /www/wwwlogs/themedge.com.error.log
    pm2 logs themedge-web

Backup manuale: /root/backup-themedge.sh

Analytics snapshot manuale:

    cd /www/wwwroot/themedge.com/api
    php artisan analytics:snapshot

Purge Cloudflare manuale:

    cd /www/wwwroot/themedge.com/api
    php artisan tinker --execute="\$a = App\Models\Article::where('slug', 'il-mio-primo-articolo')->first(); \$a->purgeCloudflareCache(); echo 'Purge inviato';"

Test API:

    curl -s https://api.themedge.com/api/articles | python3 -m json.tool | head -20
    curl -s https://api.themedge.com/api/breaking-news | python3 -m json.tool
    curl -s "https://api.themedge.com/api/articles/il-mio-primo-articolo/highlights/stats" | python3 -m json.tool

Verifica cache Cloudflare:

    curl -sI https://themedge.com/articolo/il-mio-primo-articolo | grep -iE "cf-cache-status|age"

---

## 🔑 Credenziali (nei file del server, NON in chat)

MySQL: /root/.my.cnf e /root/.laravel_db_password
Redis: /www/server/redis/redis.conf (requirepass)
Meilisearch: /root/.meilisearch_master_key
GitHub token: /root/.github-token
Laravel APP_KEY: /www/wwwroot/themedge.com/api/.env
VAPID keys: /www/wwwroot/themedge.com/api/.env
Cloudflare token: /www/wwwroot/themedge.com/api/.env
Admin panel: admin@themedge.com

Utenti di test:
editor@themedge.com → EditorPassword123! (ruolo: editor)
test.editor@themedge.com → AuthorPassword123! (ruolo: author)

---

## 🎯 Come iniziare la prossima sessione

1. Leggi questo HANDOFF

2. Esegui check iniziale:

    /root/fpm.sh
    curl -I https://themedge.com
    curl -I https://admin.themedge.com/admin
    pm2 list
    curl -sI https://themedge.com/articolo/il-mio-primo-articolo | grep -iE "cf-cache-status|age"

3. Scegli la prossima feature (vedi roadmap sopra)

4. Segui il workflow modifica

---

## 🚨 Problemi noti e fix applicati

1. PHP-FPM crash loop → /root/fpm.sh
2. OPcache validate_timestamps=Off → restart dopo modifiche
3. spatie/activitylog v5: namespace Models\Concerns\LogsActivity + Support\LogOptions, dontLogEmptyChanges()
4. web-push v11: new WebPush($auth) senza timeout int
5. Flash bianco dark mode (FOUC) → script inline nel head
6. Auto-refresh homepage → fetch + setState (no reload)
7. Hover bianco Tailwind v4 + Filament → fallback CSS
8. Redirect slug vs ID → Article::getRouteKeyName()
9. Hydration mismatch AudioPlayer → AudioPlayerClient wrapper
10. columnFullWidth() non esiste → columnSpanFull()
11. Filament Select label null → TextInput + placeholder
12. Repeater item vuoti → saveRelationshipsUsing custom
13. Cloudflare cache no-store → Cache Rule "Ignore cache-control"
14. Next.js perde BUILD_ID → sempre npm run build prima di pm2 restart

---

## 📞 URL utili

GitHub: https://github.com/Serialc0de/themedge
Sito: https://themedge.com
Admin: https://admin.themedge.com/admin
API: https://api.themedge.com/api
Cloudflare: https://dash.cloudflare.com
Docs repo: https://github.com/Serialc0de/themedge/tree/main/docs

---

## 📚 Documentazione

docs/ADMIN_PANEL.md — Guida pannello admin
docs/FEATURES.md — Guida feature avanzate
docs/PHP_FPM.md — Regola PHP-FPM
docs/TROUBLESHOOTING.md — Problemi noti
docs/API.md — API reference
docs/READER_PREMIUM.md — Reading list, TTS, share
docs/PERFORMANCE.md — Cloudflare cache
docs/RESPONSIVE.md — Breakpoint + fluid
CHANGELOG.md — Storia modifiche
SECURITY.md — Vulnerabilità documentate
