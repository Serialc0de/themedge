# 📰 Themedge

Giornale online editoriale professionale.

**Sito:** themedge.com
**Admin:** admin.themedge.com/admin

---

## 🎯 Panoramica

Themedge è una piattaforma editoriale completa con:

- Backend Laravel 13 + Filament 3 con pannello admin professionale
- Frontend Next.js 16 con tema editoriale raffinato
- API REST con Sanctum
- Ricerca full-text con Meilisearch (1ms)
- SEO completo (sitemap, robots, OpenGraph, JSON-LD, RSS)
- Workflow editoriale (draft → review → approved → published → archived)

---

## 🏗️ Stack Tecnico

| Componente | Tecnologia |
|---|---|
| Backend | Laravel 13 + Filament 3 + PHP 8.4 |
| Frontend | Next.js 16 + Tailwind CSS v4 |
| Database | MySQL 8 + Redis 7 |
| Ricerca | Meilisearch 1.54 |
| Server | Nginx 1.30 + PHP-FPM 8.4 (aaPanel) |
| CDN/WAF | Cloudflare (Full strict + HSTS) |
| Process Manager | PM2 (themedge-web) |
| CI/CD | GitHub Actions + Dependabot |

---

## 📁 Struttura

themedge/
├── api/                    Laravel 13 + Filament 3
│   ├── app/
│   │   ├── Filament/       Resources, Widgets, Pages
│   │   ├── Models/         Article, User, Category, Tag, Redirect, MediaAsset
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   └── Middleware/ HandleRedirects
│   │   └── Policies/       Shield policies
│   ├── database/migrations/
│   ├── resources/views/filament/
│   └── routes/
├── web/                    Next.js 16
│   ├── app/
│   ├── components/
│   └── public/
├── docs/                   Documentazione
├── deploy/                 Script deploy + config Nginx
└── .github/workflows/      CI/CD

---

## 🚀 Quick Start

### Backend Laravel

cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve

### Frontend Next.js

cd web
npm install
cp .env.example .env.local
npm run dev

---

## 🎛️ Pannello Admin

Accesso: admin.themedge.com/admin

### Funzionalità principali

- Utenti (/admin/users) — avatar, bio, ruoli, impersona
- Articoli (/admin/articles) — workflow editoriale completo
- Categorie (/admin/categories) — gerarchia
- Tag (/admin/tags)
- Media Library (/admin/media) — upload, gallery, copia URL
- Audit Log (/admin/activities) — storico modifiche
- Redirect (/admin/redirects) — gestione 301/302 + auto-redirect
- Dashboard (/admin) — KPI, grafici, system status

### Ruoli (Shield)

| Ruolo | Permessi | Descrizione |
|---|---|---|
| super_admin | 55 (tutti) | Amministratore |
| editor | 36 | Gestisce tutti gli articoli |
| author | 10 | Crea/modifica i propri articoli |
| contributor | 4 | Crea solo bozze |

Vedi docs/ADMIN_PANEL.md per la guida completa.

---

## 🚨 PHP-FPM — Regola Critica

MAI usare systemctl restart php-fpm-84 → crash loop garantito.

SEMPRE usare:

/root/fpm.sh

Vedi docs/PHP_FPM.md per i dettagli.

---

## 🔧 Comandi Utili

# Riavvio PHP-FPM
/root/fpm.sh

# Cache Laravel
cd /www/wwwroot/themedge.com/api
php artisan optimize:clear
php artisan filament:optimize-clear

# Log
tail -f storage/logs/laravel.log
pm2 logs themedge-web

# Backup
/root/backup-themedge.sh

---

## 📚 Documentazione

- docs/ADMIN_PANEL.md — guida completa pannello admin
- docs/PHP_FPM.md — regola critica PHP-FPM
- docs/TROUBLESHOOTING.md — problemi noti
- docs/SETUP.md — setup iniziale
- docs/DEPLOY.md — deploy in produzione

---

## 📝 Changelog

Vedi CHANGELOG.md.

---

## 📄 Licenza

Vedi LICENSE.
