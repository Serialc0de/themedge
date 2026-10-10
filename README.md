# Themedge

Giornale online editoriale costruito con Laravel 13 + Filament 3 (backend) e Next.js 16 + Tailwind v4 (frontend).

## Architettura

- `api/` → Laravel 13 + Filament 3 (backend + admin)
- `web/` → Next.js 16 + Tailwind v4 (frontend pubblico)
- `deploy/` → Script deploy, config Nginx
- `docs/` → Documentazione

## Stack

**Backend**: Laravel 13, Filament 3, MySQL 8, Redis 7, Meilisearch
**Frontend**: Next.js 16, React 19, TypeScript, Tailwind v4
**Infra**: Ubuntu, Nginx, PHP-FPM 8.4, Cloudflare, PM2

## Quick Start

Vedi [docs/SETUP.md](docs/SETUP.md).

## Licenza

Progetto privato. Tutti i diritti riservati.
