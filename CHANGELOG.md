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

---

## [2026-10-11c] — Sistema Highlight NYT-level

### Aggiunto — Sistema Highlight

**Backend:**
- Migration: `reader_identities`, `reader_highlights`, `highlight_reactions`
- Model: `ReaderIdentity` (token anonimo device-based), `ReaderHighlight`, `HighlightReaction`
- Controller: `ReaderController` (register/me), `HighlightController` (CRUD completo + config + react)
- API Resource: `HighlightResource` (con `my_reactions` + `reactions_by_emoji`), `HighlightStatsResource`
- Route: 11 endpoint sotto `/api/reader`, `/api/highlights`, `/api/articles/{slug}/highlights`
- Text anchoring W3C-style: `text_fragment` + `context_before/after` + offset
- Stats + heatmap (20 bucket) per articolo
- Reazioni emoji (5) con unique constraint e contatore denormalizzato
- Config centralizzata `config/highlights.php` (limiti, colori, emoji, feature flag)
- Endpoint pubblico `GET /api/highlights/config`
- Kill switch globale `HIGHLIGHTS_ENABLED`
- Toggle `is_public` con default configurabile

**Frontend:**
- Pagina `/highlight` con lista raggruppata per articolo, skeleton, empty state
- Context `ReaderProvider` (auto-register anonimo, token persistente)
- Context `HighlightsProvider` (CRUD + optimistic UI + coda offline)
- Hook `useMyHighlights()` per pagina personale
- Libreria `text-anchoring.ts` con fuzzy match W3C (offset → fragment → context)
- Componenti:
  - `ArticleReader`: wrapper con provider
  - `HighlightLayer`: SVG overlay per rendering highlight (non tocca HTML)
  - `HighlightPopup`: popup fluttuante su selezione
  - `HighlightMenu`: menu azioni (evidenzia, nota)
  - `HighlightDetailPopup`: popup su click highlight esistente
  - `HighlightReactions`: barra 5 emoji toggle
  - `HighlightNoteEditor`: textarea inline con auto-resize + scorciatoie
  - `HighlightOrphanNotice`: banner per highlight orfani
  - `HighlightCard`: card su pagina /highlight
  - `HighlightEditModal`: modal edit (colore, nota, privacy)
  - `Toast`: sistema notifiche con undo
- Toggle privacy (privato/pubblico) su popup e modal
- Rilevamento highlight orfani con toast una-tantum
- Badge "X persone hanno reagito" su highlight pubblici
- Optimistic update + rollback su tutte le mutazioni
- CSS: animazioni popup/toast/modal, layer SVG, responsive

**Sicurezza:**
- Rate limiting 120 req/min su highlights
- Anti-duplicato (stesso reader + offset = 409)
- Validazione da config (limiti, colori, emoji)
- Token anonimo via header `X-Reader-Token`

### Note
- Sistema device-based (no account) — cross-device sync richiede OAuth (sessione futura)
- Moderazione highlight per-articolo/policy in roadmap (sessione 4)

---

---

## [2026-10-11d] — Sessione 5: Highlight Livello 3 al 100%

### Aggiunto — Condivisione come immagine
- `ShareHighlightCard.tsx`: modal con card 1:1 catturabile
- Design Editorial fisso (indipendente da dark/light mode)
- Download PNG 2x + Web Share API nativo + copia testo
- Dipendenza: `html-to-image`

### Aggiunto — Heatmap articolo
- `HighlightHeatmap.tsx`: barre proporzionali (20 bucket)
- Top 3 frasi più evidenziate con ranking
- Contatori lettori unici + highlight pubblici
- Soglia minima 3 highlight per evitare dati inutili

### Aggiunto — Admin moderazione
- `HighlightResource` (`/admin/highlights`):
  * Tabella paginata + filtri (pubblico, colore, note, reazioni, data)
  * Azioni: Modifica, Nascondi, Ban reader, Elimina
  * Bulk: Rendi privati, Elimina multipli
- `HighlightStatsOverviewWidget`: KPI totali/pubblici/note/reazioni

### Aggiunto — GDPR
- `GET /api/reader/me/export`: JSON con tutti i dati (Art. 15/20)
- `DELETE /api/reader/me`: cancella tutto (Art. 17)
- Pagina `/privacy/dati-personali` con bottoni export/delete
- Link "I tuoi dati" nel footer

### Modificato — Reazioni immutabili
- Migration: unique constraint su `(highlight_id, reader_id)`
- `HighlightReaction::react()`: immutabile, ritorna null se già reagito
- `HighlightController::react()`: 409 se già reagito
- `HighlightController::unreact()`: 405 (immutabile)
- `HighlightReactions.tsx`: emoji disabilitate dopo la prima reazione
- Toast "Hai già reagito" su tentativi di modifica

### Fix
- `HighlightResource`: form semplificato (TextInput vs Select relationship)
- Rimosso `columnFullWidth()` inesistente in Filament 3
- Fix unique constraint dopo cambio reazioni

---

---

## [2026-10-12] — Pacchetto Credibilità

### Aggiunto — Correzioni pubbliche inline
- Componente `ArticleCorrections` con badge collassabile
- Diff "Prima / Dopo" con testo barrato vs corretto
- Colore dinamico per severity (minor/major/retraction)
- Integrato in `ArticleReader` (entrambi i return)

### Aggiunto — Fonti verificabili
- Migration `article_sources` (8 tipi: official, study, news, social, archive, video, dataset, other)
- Model `ArticleSource` con accessor icon/label/domain
- API `GET /api/articles/{slug}/sources`
- Admin Repeater in ArticleResource
- Componente `ArticleSources` con badge tipo colorato, verificato, link archiviato

### Aggiunto — Approfondimenti curati
- Migration `article_related` (self-referencing many-to-many)
- Relazione `Article->relatedCurated()`
- API `GET /api/articles/{slug}/curated`
- Admin Repeater con saveRelationshipsUsing custom
- Componente `CuratedReads` con note editoriali + bordo tratteggiato

### Fix
- Hook `creating()` Article: blocca articoli senza titolo (evita crash Filament)
- Repeater relatedCurated: `saveRelationshipsUsing` scarta item null
- Sezione CuratedReads: dark mode ottimizzato (bg-gray-50 / bg-gray-800)

---

---

## [2026-10-12] — Pacchetto Lettore Premium

### Aggiunto — Reading List ("Salva per dopo")
- Context `ReadingListProvider` con localStorage
- Componente `SaveButton.tsx` per articolo
- Componente `SaveIconButton.tsx` floating per card
- Componente `ReadingListNavIcon.tsx` con badge conteggio navbar
- Componente `ReadingListClient.tsx` pagina `/salvati`
- Pagina `app/salvati/page.tsx`
- Integrazione in `ArticleCard.tsx` (4 varianti) + `ArticleReader.tsx` (2 return)
- Provider in `layout.tsx`

### Aggiunto — Text-to-Speech (TTS)
- Hook `useSpeechSynthesis` in `lib/tts.tsx`
- Utility `tts-utils.ts` (extractText, estimateDuration, pickItalianVoice)
- Componente `AudioPlayer.tsx` (play/pausa/velocità/progress)
- Componente `AudioPlayerClient.tsx` (wrapper SSR-safe)
- Supporto voci italiane + warning se assenti
- Velocità 0.5x, 0.75x, 1x, 1.25x, 1.5x, 2x (persistita in localStorage)

### Aggiunto — Share nativi
- Componente `ShareButtons.tsx` con Web Share API
- Fallback desktop: Twitter/X, WhatsApp, LinkedIn, copia link
- Toast feedback "Copiato!"
- Integrato in `ArticleReader.tsx` (2 return)

---

## [2026-10-12] — Responsive + Performance

### Fix Responsive — Header + Navbar
- `components/MobileMenu.tsx` drawer da destra (hamburger fino a xl)
- `app/globals.css`: animazioni fade-in, slide-in-right, overflow-x hidden
- `app/layout.tsx`:
  * Hamburger visibile < 1280px (xl)
  * Navbar visibile ≥ 1280px (xl)
  * Logo centrato responsive con `clamp(1.875rem, 4vw+1rem, 6rem)`
  * Tagline fluid `clamp(0.875rem, 1.5vw, 1.125rem)`
  * Top bar data abbreviata su mobile

### Fix Responsive — Fix Batch 1
- Footer grid: `sm:grid-cols-2 lg:grid-cols-5`
- Byline articolo: 2 righe mobile / 1 riga desktop
- ShareButtons: tap target h-11 (44px)
- AudioPlayer: tap target aumentato (h-9 velocità, h-10 azioni)
- ShareHighlightCard: max-width responsive con clamp
- HighlightDetailPopup: width già clampato
- MobileMenu: hamburger 44px

### Performance — Cloudflare CDN
- Cloudflare Cache Rule per HTML:
  * Edge TTL 2h su `/articolo/*`, `/categoria/*`, `/tag/*`, `/autore/*`
  * TTL 60 sec su homepage
  * Ignore cache-control (Next.js invia no-store)
  * Serve stale while revalidating
- `config/services.php`: cloudflare token + zone_id
- `Article::purgeCloudflareCache()` in `saved()`:
  * Purge selettivo articolo + homepage + categoria + autore
  * Solo per articoli pubblicati
  * Log warning se fallisce
- `.env`: `CLOUDFLARE_API_TOKEN` + `CLOUDFLARE_ZONE_ID`

### Risultati Performance
- **TTFB articolo:** 813ms → 30-80ms (**-90%**)
- **TTFB homepage:** ~800ms → 35-65ms (**-90%**)
- **Cache Cloudflare:** BYPASS → HIT
- **Lighthouse Performance stimato:** ~75 → **~95**

### Pulizia
- Rimossi `.bak` dal repo
- `.gitignore` aggiornato con `*.bak*` + `*.generated*`

---

---

## [2026-10-12] — Fix timeAgo + relative + known issue 418

### Fixati
- **lib/time.ts**: funzione `timeAgo` unificata (era duplicata in 8 file con logica divergente)
  - Risolto "1 giorni fa" (mancava singolare) e "3 giornoi fa" (build vecchia)
- **6 `relative` mancanti** su contenitori `<Image fill>`:
  - `app/redazione`, `app/diretta/[slug]`, `app/ultimora`, `app/dirette` (2), `components/ArticleCard` (variant list)
  - → Immagini non scappano più nell'header
- **next.config.ts**: aggiunti hostname per thumbnail esterne (YouTube, Vimeo, Twitter, Instagram, TikTok, Spotify)
- **ArticleCorrections**: `suppressHydrationWarning` su `toLocaleDateString`
- **GlossaryTooltipManager**: fix posizionamento (tooltip sopra il termine, con freccia) + riattivato in layout
- **useGlossaryHighlighting**: delay 2s per evitare mismatch DOM

### Known issue
- **React error #418** (hydration mismatch) — non risolto
  - Impatto utente: nullo (React recupera, sito funziona)
  - Impatto SEO: nullo
  - Cause investigate: timeAgo, gravatar, DOMPurify, toLocaleDateString, BLUR_DATA_URL, dynamic import, GlossaryTooltipManager — tutte fixate, ma il 418 persiste
  - Prossimo step: React DevTools in dev mode (non urgente)

