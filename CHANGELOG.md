# Changelog

Tutte le modifiche importanti del progetto sono documentate qui.

---

## [Unreleased]

### Planned
- Notifiche email workflow
- Commenti articoli
- Newsletter
- Analytics (Umami/Plausible)
- Sentry error tracking
- PWA + push notifications
- Versioning visivo articoli
- Paywall / abbonamenti

---

## [2026-10-10] — Admin Panel Completo

### Aggiunto

**UserResource**
- Gestione utenti con avatar, bio, job_title, twitter_handle
- Azioni Impersona e Attiva/Disattiva
- Filtri per ruolo e stato account

**ArticleResource**
- Workflow editoriale: draft, review, approved, scheduled, published, archived
- Azioni: submit_review, approve, reject, publish, archive
- Editor SEO con 5 check automatici
- Anteprima Google live
- Pulsante Anteprima frontend
- Filtro automatico per ruolo (author vede solo i suoi)

**Media Library**
- Modello MediaAsset
- Upload multiplo con drag and drop
- Azione Copia URL via modal
- Widget Media recenti

**Redirect Manager**
- CRUD redirect 301/302/307/308
- Contatore hit + last_hit_at
- Middleware HandleRedirects
- Auto-redirect su cambio slug articolo
- Widget Redirect più usati

**Audit Log**
- spatie/laravel-activitylog v5
- Traccia Article, User, Category, Tag, Redirect
- Resource /admin/activities con filtri
- Widget Ultime attività

**Dashboard**
- KPI: articoli, views, in review, autori attivi
- Grafico ApexCharts 30 giorni
- Top 5 articoli più letti
- Top 5 autori più prolifici
- Pending review widget
- System status (MySQL, Redis, Meilisearch, Storage, Cache, Coda)
- Media recenti
- Ultime attività
- Redirect più usati

### Migration
- editorial_fields_to_users_table (avatar, bio, is_active, last_login_at, job_title, twitter_handle)
- workflow_fields_to_articles_table (review_requested_at, approved_at, approved_by, rejection_reason, scheduled_for, revision_count)
- media_assets_table
- redirects_table
- activity_log_table

### Sicurezza
- Shield: 4 ruoli (super_admin, editor, author, contributor)
- 55 permessi generati automaticamente
- Policy per User, Article, Category, Tag, Role

### Dipendenze
- spatie/laravel-activitylog ^5.1

### Modificato
- bootstrap/app.php — registrato middleware HandleRedirects
- Modelli Article, User, Category, Tag — aggiunto LogsActivity trait

### Fix
- spatie/activitylog v5 namespace:
  - Spatie\Activitylog\Models\Concerns\LogsActivity (era Traits\LogsActivity)
  - Spatie\Activitylog\Support\LogOptions (era Activitylog\LogOptions)
  - dontSubmitEmptyLogs() → dontLogEmptyChanges()
- PHP-FPM — mai systemctl, sempre /root/fpm.sh
- eslint.config.mjs — flat config per eslint-config-next@16

---

## [2026-10-09] — Setup iniziale

### Aggiunto
- VPS Ubuntu 26.04 con Nginx, PHP-FPM 8.4, MySQL 8, Redis 7, Meilisearch 1.54
- Laravel 13 + Filament 3 + Shield + Breezy + ApexCharts
- Next.js 16 + Tailwind v4 con tema editoriale
- API REST con Sanctum
- SSL wildcard *.themedge.com
- Cloudflare Full strict + HSTS
- CI/CD GitHub Actions
- Dependabot
- Backup automatici notturni

### Frontend Next.js
- Homepage, articolo, categoria, tag, autore, ricerca
- SEO completo (sitemap, robots, RSS, OpenGraph, JSON-LD)
- Dark mode, cookie banner, progress bar, 404

---

## [2026-10-09] — Fix e Stabilizzazione

### Fix
- PHP-FPM socket occupato
- OPcache validate_timestamps=Off
- Configurazione Nginx vhost admin
- TrustProxies in bootstrap/app.php
- Storage link

---

## Legenda

- Aggiunto — nuove funzionalità
- Modificato — modifiche a funzionalità esistenti
- Deprecato — funzionalità in disuso
- Rimosso — funzionalità eliminate
- Fix — bug fix
- Sicurezza — fix di sicurezza
