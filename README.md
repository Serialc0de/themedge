# Themedge

[![API Tests](https://github.com/Serialc0de/themedge/actions/workflows/api.yml/badge.svg)](https://github.com/Serialc0de/themedge/actions/workflows/api.yml)
[![Web Tests](https://github.com/Serialc0de/themedge/actions/workflows/web.yml/badge.svg)](https://github.com/Serialc0de/themedge/actions/workflows/web.yml)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

**Giornale online editoriale** costruito con Laravel 13 + Filament 3 (backend) e Next.js 16 + Tailwind v4 (frontend).

## Architettura

themedge/
├── api/ → Laravel 13 + Filament 3 (backend + admin)
├── web/ → Next.js 16 + Tailwind v4 (frontend pubblico)
├── deploy/ → Script deploy, config Nginx
├── docs/ → Documentazione
└── .github/ → CI/CD workflows



## Stack tecnico

**Backend (`api/`)**
- Laravel 13.35
- Filament 3.3 (admin panel)
- Filament Shield (ruoli + permessi)
- Filament Excel (export)
- Filament Apex Charts (grafici)
- Spatie Media Library (media)
- Laravel Scout + Meilisearch (ricerca)
- Sanctum (API auth)
- MySQL 8 + Redis 7

**Frontend (`web/`)**
- Next.js 16 (App Router)
- React 19
- TypeScript
- Tailwind CSS v4
- Playfair Display + Inter

**Infrastruttura**
- VPS Ubuntu 26.04
- Nginx 1.30 + PHP-FPM 8.4
- Cloudflare (CDN + SSL + WAF)
- PM2 (process manager)
- Meilisearch (ricerca)
- Fail2ban + UFW

## Documentazione

- [Setup](docs/SETUP.md)
- [Deploy](docs/DEPLOY.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## Sicurezza

- **NON** committare `.env`
- Usa `.env.example` come template
- Ruota credenziali periodicamente

## Licenza

Progetto privato. Tutti i diritti riservati.

---

**Made with ❤️ by Themedge Team**

