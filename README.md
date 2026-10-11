# 📰 Themedge

Giornale online editoriale professionale.

**Sito:** [themedge.com](https://themedge.com) · **Admin:** [admin.themedge.com/admin](https://admin.themedge.com/admin)

---

## 🎯 Panoramica

Themedge è una piattaforma editoriale completa con:

- **Backend Laravel 13 + Filament 3** con pannello admin professionale
- **Frontend Next.js 16** con tema editoriale raffinato
- **API REST** con Sanctum
- **Ricerca full-text** con Meilisearch (1ms)
- **SEO completo** (sitemap, robots, OpenGraph, JSON-LD, RSS)
- **Workflow editoriale** completo (draft → review → approved → published → archived)
- **Breaking News configurabile** con banner + push notifications
- **Live Blog** per dirette in tempo reale
- **Push Notifications PWA** cross-platform
- **Commenti** con moderazione
- **Analytics Editoriale** con KPI e report
- **Contenuto editoriale** (sondaggi, correzioni, segnalazioni, redazione)

---

## 🏗️ Stack Tecnico

| Componente | Tecnologia |
|---|---|
| **Backend** | Laravel 13 + Filament 3 + PHP 8.4 |
| **Frontend** | Next.js 16 + Tailwind CSS v4 |
| **Database** | MySQL 8 + Redis 7 |
| **Ricerca** | Meilisearch 1.54 |
| **Server** | Nginx 1.30 + PHP-FPM 8.4 (aaPanel) |
| **CDN/WAF** | Cloudflare (Full strict + HSTS) |
| **Process Manager** | PM2 (themedge-web) |
| **CI/CD** | GitHub Actions + Dependabot |

---

## 📁 Struttura

themedge/
├── api/                    Laravel 13 + Filament 3
│   ├── app/
│   │   ├── Filament/       Resources, Widgets, Pages
│   │   ├── Models/         15 modelli Eloquent
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   └── Middleware/
│   │   └── Services/
│   ├── database/migrations/
│   ├── resources/views/filament/
│   └── routes/
├── web/                    Next.js 16
│   ├── app/
│   ├── components/
│   ├── lib/
│   └── public/
├── docs/                   Documentazione
├── .github/workflows/      CI/CD
└── README.md

---

## 🎛️ Pannello Admin

Accesso: admin.themedge.com/admin

### Funzionalità principali

Contenuti:
- Articoli con workflow editoriale completo
- Categorie e tag
- Media Library con upload multiplo
- Redirect manager (301/302)
- Breaking News configurabili
- Live Blog con stream in tempo reale
- Sondaggi
- Correzioni / Errata
- Commenti con moderazione

Redazione:
- Utenti con avatar, bio, ruoli
- Squadra editoriale
- Ruoli editoriali
- Segnalazioni pubbliche

SEO:
- Impostazioni SEO globali
- Robots.txt editor
- Sitemap
- Breaking News settings

Sistema:
- Audit Log
- Analytics Editoriale
- Push Subscriptions
- Storico invii push
- Dashboard con 14+ widget

### Ruoli (Shield)

| Ruolo | Permessi | Descrizione |
|---|---|---|
| super_admin | 55 (tutti) | Amministratore |
| editor | 36 | Gestisce tutti gli articoli |
| author | 10 | Crea/modifica i propri articoli |
| contributor | 4 | Crea solo bozze |

Vedi docs/ADMIN_PANEL.md per la guida completa.

---

## 🌐 Frontend Pubblico

Pagine disponibili:

- / — Homepage con hero, sezioni, più letti
- /articolo/[slug] — Articolo con commenti, correlati, JSON-LD
- /categoria/[slug], /tag/[slug], /autore/[id]
- /cerca — Ricerca istantanea Meilisearch
- /ultimora — Archivio breaking news
- /dirette, /diretta/[slug] — Live Blog
- /redazione, /correzioni, /segnala
- /chi-siamo, /contatti, /privacy
- /rss, /rss/[category]
- /sitemap.xml, /robots.txt

---

## 🚀 Quick Start (sviluppo)

Backend Laravel:

cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve

Frontend Next.js:

cd web
npm install
cp .env.example .env.local
npm run dev

---

## 🚨 PHP-FPM — Regola Critica

MAI usare systemctl restart php-fpm-84 → crash loop garantito.

SEMPRE usare:

/root/fpm.sh

Vedi docs/PHP_FPM.md per i dettagli.

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

Backup:
/root/backup-themedge.sh

Analytics snapshot:
cd /www/wwwroot/themedge.com/api
php artisan analytics:snapshot

---

## 📚 Documentazione

- docs/ADMIN_PANEL.md — guida completa pannello admin
- docs/FEATURES.md — breaking news, live blog, push, commenti
- docs/PHP_FPM.md — regola critica PHP-FPM
- docs/TROUBLESHOOTING.md — problemi noti
- HANDOFF.md — passaggio nuovo operatore
- CHANGELOG.md — storia modifiche

---

## 📝 Changelog

Vedi CHANGELOG.md.

---

## 📄 Licenza

Vedi LICENSE.
