# 🤝 Handoff — Progetto Themedge

Data ultima modifica: 11 ottobre 2026
Ore totali: ~42
Stato: 90% completo
Prossime feature: Paywall, Newsletter, Notifiche email

---

## 🚨 Regola PHP-FPM

MAI usare systemctl restart php-fpm-84 → crash loop garantito.

SEMPRE usare:

/root/fpm.sh

---

## 📊 Stato del progetto

### ✅ Implementato

Pannello Admin (23+ funzionalità):
- UserResource + Shield + ArticleResource + workflow editoriale
- Editor SEO con 9 check + anteprima Google + social
- Media Library, Audit Log, Versioning articoli
- Redirect Manager, Dashboard 14 widget
- SEO Toolkit (settings, robots, sitemap)
- Sondaggi, Correzioni, Segnalazioni, Squadra editoriale
- Breaking News System configurabile
- Live Blog con stream in tempo reale
- Push Notifications PWA cross-platform
- Commenti con moderazione
- Analytics Editoriale (KPI + report + export CSV)

Frontend Next.js:
- Homepage, articoli, categorie, tag, autori, ricerca
- SEO completo (sitemap, robots, RSS, OG, JSON-LD)
- Dark mode (senza FOUC)
- /redazione, /correzioni, /segnala
- /ultimora, /dirette, /diretta/[slug]
- PWA installabile (Android + iOS)
- Push notifications attive
- Commenti articoli con thread

Infrastruttura:
- SSL wildcard + HSTS (A+ su SSL Labs)
- Cloudflare Full strict
- PHP-FPM stabile
- Backup automatici notturni (cron 3:00)
- CI/CD GitHub Actions verdi

### ⏳ Da implementare (10% rimanente)

- Paywall / Abbonamenti (6-8 ore)
  Stripe, metered paywall (3 articoli gratis/mese), area lettori
- Newsletter Editor (4-6 ore)
  Template, segmentazione, invio, statistiche
- Notifiche email (1-2 ore)
  Commenti, workflow editoriale, richiede SMTP
- UX Avanzata
  Reading list, Text-to-Speech, articoli correlati AI
- Video/Embed (2-3 ore)
  YouTube, Twitter, Instagram

---

## 📁 Workflow modifica

1. Modifica in PRODUZIONE (/www/wwwroot/themedge.com/api o /web)

2. Fix ownership:
   chown -R www:www /www/wwwroot/themedge.com/api/app/
   chown -R www:www /www/wwwroot/themedge.com/web/components/

3. Se PHP:
   cd /www/wwwroot/themedge.com/api
   php -l file.php
   php artisan optimize:clear
   php artisan filament:optimize-clear
   /root/fpm.sh

4. Se frontend Next.js:
   cd /www/wwwroot/themedge.com/web
   export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
   npm run build
   pm2 restart themedge-web

5. Sync da produzione a repo:
   rsync -av --delete --exclude='*.bak.*' --exclude='*.generated' \
     /www/wwwroot/themedge.com/api/app/ \
     /www/wwwroot/themedge-repo/api/app/

   rsync -av /www/wwwroot/themedge.com/api/resources/views/filament/ \
     /www/wwwroot/themedge-repo/api/resources/views/filament/

   rsync -av /www/wwwroot/themedge.com/api/routes/ \
     /www/wwwroot/themedge-repo/api/routes/

   rsync -av /www/wwwroot/themedge.com/api/database/migrations/ \
     /www/wwwroot/themedge-repo/api/database/migrations/

   rsync -av --delete --exclude='node_modules' --exclude='.next' \
     --exclude='.env.local' \
     /www/wwwroot/themedge.com/web/app/ \
     /www/wwwroot/themedge-repo/web/app/

   rsync -av /www/wwwroot/themedge.com/web/components/ \
     /www/wwwroot/themedge-repo/web/components/

   rsync -av /www/wwwroot/themedge.com/web/lib/ \
     /www/wwwroot/themedge-repo/web/lib/

6. Commit + push:
   cd /www/wwwroot/themedge-repo
   git add .
   git commit -m "feat: descrizione"
   git push origin main

---

## 🔧 Comandi Utili

Riavvio PHP-FPM:
/root/fpm.sh

Cache Laravel + Filament:
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

Rebuild frontend:
cd /www/wwwroot/themedge.com/web
export PATH=/www/server/nodejs/v22.23.3/bin:$PATH
npm run build
pm2 restart themedge-web

Log:
tail -f /www/wwwroot/themedge.com/api/storage/logs/laravel.log
tail -f /www/wwwlogs/admin.themedge.com.error.log
pm2 logs themedge-web

Backup manuale:
/root/backup-themedge.sh

Analytics snapshot manuale:
cd /www/wwwroot/themedge.com/api
php artisan analytics:snapshot

---

## 🔑 Credenziali (nei file del server, NON in chat)

MySQL:           /root/.my.cnf e /root/.laravel_db_password
Redis:           /www/server/redis/redis.conf (requirepass)
Meilisearch:     /root/.meilisearch_master_key
GitHub token:    /root/.github-token
Laravel APP_KEY: /www/wwwroot/themedge.com/api/.env
VAPID keys:      /www/wwwroot/themedge.com/api/.env
Admin panel:     admin@themedge.com / (password scelta)

Utenti di test:
editor@themedge.com → EditorPassword123!
test.editor@themedge.com → AuthorPassword123!

---

## 🎯 Come iniziare la prossima sessione

1. Leggi questo HANDOFF

2. Esegui check iniziale:
   /root/fpm.sh
   curl -I https://themedge.com
   curl -I https://admin.themedge.com/admin
   pm2 list

3. Scegli la prossima feature:
   - Paywall / Abbonamenti
   - Newsletter
   - Notifiche email
   - UX Avanzata
   - Video/Embed

4. Segui il workflow modifica (sopra)

---

## 🚨 Problemi noti e fix applicati

1. PHP-FPM crash loop → /root/fpm.sh
2. OPcache validate_timestamps=Off → restart dopo modifiche
3. spatie/activitylog v5 namespace:
   - use Spatie\Activitylog\Models\Concerns\LogsActivity;
   - use Spatie\Activitylog\Support\LogOptions;
   - dontSubmitEmptyLogs() → dontLogEmptyChanges()
4. web-push v11 costruttore: new WebPush($auth) senza timeout int
5. Flash bianco dark mode (FOUC) → script inline nel head
6. Auto-refresh homepage → fetch + setState (no reload)
7. Hover bianco Tailwind v4 + Filament → fallback CSS
8. Redirect slug vs ID → Article.getRouteKeyName()

---

## 📞 URL utili

Admin:  https://admin.themedge.com/admin
Sito:   https://themedge.com
API:    https://api.themedge.com/api/articles
GitHub: https://github.com/Serialc0de/themedge
