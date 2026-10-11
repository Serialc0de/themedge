# Changelog

Tutte le modifiche importanti del progetto sono documentate qui.

---

## [Unreleased]

### Planned
- Paywall / Abbonamenti (Stripe)
- Newsletter Editor
- Notifiche email automatiche
- Reading list lettori
- Text-to-Speech

---

---

## [2026-10-11b] — Sistema Embed completo

### Aggiunto — Sistema Embed

**Backend:**
- Servizio `HtmlSanitizer` basato su `symfony/html-sanitizer` con allow-list configurabile
- Servizio `OEmbedResolver` con 8 provider: YouTube, Vimeo, Spotify, Twitter/X, Instagram, TikTok, Generic
- Endpoint `GET /api/oembed?url=...` per risolvere URL in HTML embed
- Endpoint `GET /api/oembed/providers` per elenco provider supportati
- Hook `Article::saving()` per sanitizzazione automatica del content
- Colonna `content_raw` per backup del contenuto originale (rollback)
- Comando `articles:resanitize` con `--dry-run`, `--force`, `--id`
- Action Filament `InsertEmbedAction` in ArticleResource
- Rate limiting su `/api/oembed` (60 req/min)

**Frontend:**
- Componente `ArticleBody` con parser embed e sanitizzazione output
- Componenti embed: `VideoEmbed`, `SpotifyEmbed`, `GenericEmbed`, `TwitterEmbed`, `InstagramEmbed`, `TikTokEmbed`
- `VideoEmbed` con click-to-load + thumbnail YouTube (GDPR-friendly)
- Integrazione in `/articolo/[slug]` via `<ArticleBody />`
- Wrapper `sanitizeHtml()` con `isomorphic-dompurify`
- Stili embed in `globals.css`

### Sicurezza
- Doppia sanitizzazione: input (Laravel) + output (Next.js)
- Blocco SSRF: iframe verso IP interni/localhost rimossi
- Blocco XSS: `<script>`, event handlers (`onclick`, `onerror`), `javascript:` href
- Allow-list iframe host per provider
- Force HTTPS su tutti gli URL
- Rate limiting endpoint oEmbed

### Dipendenze
- Backend: `symfony/html-sanitizer ^7.4`
- Frontend: `isomorphic-dompurify`, `react-social-media-embed`

### Test
- XSS `<script>alert(1)</script>` → rimosso
- iframe YouTube → preservato (no-cookie)
- iframe evil.com → bloccato (`host not allowed`)
- iframe 192.168.x.x → bloccato (`internal host`)

---

## [2026-10-11] — Feature avanzate + Commenti + Analytics

### Aggiunto

**Breaking News System configurabile**
- 5 stili banner (pulse_red, solid_red, black_red, yellow_warning, custom)
- 4 modalità posizione (global, home, custom, disabled)
- 3 tipi articolo: Flash (1h), Breaking (24h), Developing (72h)
- Priorità e scadenza automatica
- Override colori per articolo
- Badge ULTIM'ORA sulle card
- Pagina pubblica /ultimora (archivio 72h)
- Pagina admin /admin/seo/breaking
- API /api/breaking-news

**Live Blog System**
- Dirette con stream aggiornamenti in tempo reale
- 7 tipi aggiornamento (update, breaking, analysis, quote, photo, video, result)
- Auto-refresh ogni 30s
- Iscrizione email
- Pagine /dirette e /diretta/[slug]
- Admin /admin/live-blogs
- API /api/live-blogs

**Push Notifications PWA**
- PWA installabile (manifest + service worker)
- Push cross-platform (desktop, Android, iOS 16.4+)
- VAPID keys
- Admin /admin/push-subscriptions
- Storico invii /admin/push-notification-logs
- Integrazione automatica breaking news + live blog
- Istruzioni iOS per installazione

**Commenti articoli**
- Sistema custom con moderazione
- Thread risposte annidate
- Moderazione admin (approva/rifiuta/spam/rispondi)
- Rate limiting (5/10min per IP)
- Honeypot + filtro spam keyword
- Gravatar automatico
- Widget dashboard PendingCommentsWidget
- API /api/articles/{slug}/comments

**Analytics Editoriale**
- Pagina /admin/analytics con KPI
- Grafico andamento 30 giorni
- Top 10 articoli/autori/categorie
- Trending topics
- Export CSV
- Snapshot giornalieri automatici (23:55)
- Widget EditorialKpiWidget

**Contenuto editoriale**
- Squadra redazione (/admin/redazione/squadra + /redazione)
- Ruoli editoriali (Direttore, Caporedattore, ecc.)
- Sondaggi (/admin/polls + Poll.tsx)
- Correzioni (/admin/corrections + /correzioni)
- Segnalazioni (/admin/tip-submissions + /segnala)
- Social links dinamici
- Analytics GA/Plausible/Umami integrabili

### Fix

- **Anti-FOUC dark mode**: script inline nel head applica il tema PRIMA del render
- **Auto-refresh homepage**: BreakingBanner usa fetch + setState invece di window.location.reload()
- **Hover bianco** in /admin/analytics: rimosso hover:bg-gray-50 (conflitto Tailwind v4 + Filament)
- **Redirect slug vs ID**: Article.getRouteKeyName() = request()->is('admin/*') ? 'id' : 'slug'
- **web-push v11**: costruttore cambiato (new WebPush($auth) senza timeout int)
- **activitylog v5**: namespace Models\Concerns\LogsActivity + Support\LogOptions

### Migration aggiunte
- add_breaking_news_to_articles
- add_breaking_overrides_to_articles
- create_media_assets_table
- create_redirects_table
- create_broken_links_table
- create_seo_settings_table
- create_editorial_roles_table
- add_editorial_fields_to_users
- create_polls_table (polls, poll_options, poll_votes)
- create_corrections_table
- create_tip_submissions_table
- create_live_blogs_table (live_blogs, live_blog_updates, live_blog_subscribers)
- create_push_subscriptions_table (push_subscriptions, push_notifications_log)
- create_comments_table
- create_article_daily_stats_table

### Modello/Aggiornato
- Article (breaking, comments, scope, helper)
- User (editorial_role, show_in_editorial_board)
- SeoSetting (metodo get con filter_var)

---

## [2026-10-10] — Admin Panel Completo

### Aggiunto
- UserResource (avatar, bio, ruoli, impersona)
- Shield (4 ruoli, 55 permessi)
- ArticleResource con workflow editoriale
- Editor SEO (9 check + anteprima Google + social)
- Media Library
- Audit Log
- Redirect Manager
- Dashboard con 10+ widget
- SEO Toolkit (settings, robots, sitemap)
- Versioning articoli con diff
- Fix spatie/activitylog v5
- PHP-FPM `/root/fpm.sh`

---

## [2026-10-09] — Setup iniziale

### Aggiunto
- VPS Ubuntu 26.04 con Nginx, PHP-FPM 8.4, MySQL 8, Redis 7, Meilisearch 1.54
- Laravel 13 + Filament 3 + Shield + Breezy + ApexCharts
- Next.js 16 + Tailwind v4
- API REST con Sanctum
- SSL wildcard *.themedge.com
- Cloudflare Full strict + HSTS
- CI/CD GitHub Actions
- Dependabot
- Backup automatici notturni
- Frontend: homepage, articolo, categoria, tag, autore, ricerca
- SEO completo (sitemap, robots, RSS, OG, JSON-LD)
- UX (dark mode, cookie banner, progress bar, 404)

### Fix
- PHP-FPM socket
- OPcache validate_timestamps
- TrustProxies in bootstrap/app.php
- Storage link

---

## Legenda

- **Aggiunto** — nuove funzionalità
- **Modificato** — modifiche a funzionalità esistenti
- **Fix** — bug fix
- **Sicurezza** — fix di sicurezza
