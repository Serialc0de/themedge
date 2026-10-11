# 📚 Guida alle Feature di Themedge

Documentazione completa di tutte le feature avanzate.

---

## 🚨 Breaking News System

### Admin
- Pagina impostazioni: /admin/seo/breaking
- Widget: Breaking News attive in dashboard
- Azione articolo: "Attiva Breaking" nel menu azioni

### Opzioni
- Modalità: Global / Home / Custom / Disabled
- Stile: Pulse Red / Solid Red / Black Red / Yellow Warning / Custom
- Auto-refresh: ON/OFF (con intervallo in secondi)
- Dismissibile: ON/OFF (con ore di persistenza)

### Tipi articolo
| Tipo | Durata | Uso |
|---|---|---|
| Flash | 1 ora | Notizia urgentissima |
| Breaking | 24 ore | Notizia importante |
| Developing | 72 ore | Storia in sviluppo |

### Frontend
- Banner in cima al sito (dinamico da API)
- Badge ULTIM'ORA sulle card
- Pagina /ultimora con archivio 72h

---

## 🔴 Live Blog

### Admin
- Resource: /admin/live-blogs
- Azione: "Vai in diretta" / "Chiudi diretta"
- Aggiornamenti: RelationManager nella pagina di edit

### Tipi aggiornamento
- Aggiornamento (update)
- Ultim'ora (breaking) - invia push
- Analisi (analysis)
- Dichiarazione (quote)
- Foto (photo)
- Video (video)
- Risultato (result)

### Frontend
- /dirette — lista dirette live + concluse
- /diretta/[slug] — stream live con auto-refresh 30s

---

## 📱 Push Notifications PWA

### Setup (una tantum)
1. Genera VAPID keys: php artisan tinker → VAPID::createVapidKeys()
2. Aggiungi a .env:
   VAPID_PUBLIC_KEY=...
   VAPID_PRIVATE_KEY=...
   VAPID_SUBJECT=mailto:admin@themedge.com

### Admin
- Iscritti: /admin/push-subscriptions
- Storico invii: /admin/push-notification-logs
- Azione: "Invia notifica" (bulk)

### Integrazione automatica
- Attivazione breaking news → push automatica
- Live update tipo "breaking" → push automatica

### Frontend
- Pulsante nel footer (variants: button, banner, compact)
- Rileva iOS (necessita installazione PWA)
- Service worker /sw.js

### Supporto
| Piattaforma | Push |
|---|---|
| Desktop Chrome/Firefox/Edge | Sì |
| Android Chrome | Sì |
| iOS 16.4+ Safari (PWA installata) | Sì |
| iOS Chrome/Firefox | No |
| iOS < 16.4 | No |

---

## 💬 Commenti

### Admin
- Moderazione: /admin/comments
- Azioni: Approva / Rifiuta / Spam / Rispondi
- Filtri: stato, data
- Widget: PendingCommentsWidget in dashboard

### Anti-spam
- Rate limiting (5 commenti/10min per IP)
- Honeypot field
- Filtro spam keyword
- Filtro link multipli (>= 3)

### Frontend
- Sezione commenti in /articolo/[slug]
- Thread risposte annidate
- Gravatar automatico

---

## 📊 Analytics Editoriale

### Admin
- Dashboard: /admin/analytics
- KPI: 5 cards (articoli, views, commenti, pending, autori)
- Report: Top 10 articoli/autori/categorie, trending topics
- Export CSV: articoli, autori

### Snapshot
- Automatico ogni giorno alle 23:55
- Manuale: php artisan analytics:snapshot

---

## 📁 Contenuto editoriale

### Sondaggi (/admin/polls)
- Domanda + opzioni
- Voti con IP tracking
- Frontend: componente Poll negli articoli

### Correzioni (/admin/corrections)
- Testo originale + corretto + nota
- Severità: minor/major/retraction
- Frontend: /correzioni

### Segnalazioni (/admin/tip-submissions)
- Form pubblico /segnala
- Moderazione con stato (new, in_review, published, rejected)

### Squadra editoriale (/admin/redazione/squadra)
- Ruoli editoriali (/admin/editorial-roles)
- Display pubblico in /redazione
- Ordine personalizzabile

---

## 🔀 Redirect Manager

### Admin
- Resource: /admin/redirects
- Codici: 301, 302, 307, 308
- Auto-redirect: cambio slug articolo → redirect creato

### Middleware
- HandleRedirects intercetta 404
- Cerca match in tabella redirects
- Se trovato, redirect + incremento hits

---

## 🔗 Link utili

- ADMIN_PANEL.md — guida pannello admin
- PHP_FPM.md — regola PHP-FPM
- TROUBLESHOOTING.md — problemi noti
---
## Sistema Embed

### Provider supportati

| Provider | Tipo | Server-side | Client-side |
|----------|------|-------------|-------------|
| YouTube | iframe no-cookie | si | Click-to-load |
| Vimeo | iframe | si | Click-to-load |
| Spotify | iframe | si | Auto |
| Twitter / X | blockquote + widgets.js | no | Lazy |
| Instagram | blockquote + embed.js | no | Lazy |
| TikTok | blockquote + embed.js | no | Lazy |
| Generic | iframe | si | Auto |

### Admin - Inserisci embed

1. Vai su /admin/articles
2. Clicca l'icona Inserisci embed su un articolo
3. Incolla URL (esempio: https://www.youtube.com/watch?v=...)
4. Clicca Genera HTML
5. Copia l'HTML e incollalo nel RichEditor dell'articolo

### API oEmbed

Risolvi URL: https://api.themedge.com/api/oembed?url=...
Lista provider: https://api.themedge.com/api/oembed/providers

### Sicurezza

- Doppia sanitizzazione: input (Laravel) + output (Next.js)
- XSS: script, onerror, onclick, javascript rimosso
- SSRF: iframe verso 192.168.x, 10.x, localhost, 127.0.0.1 rimossi
- Allow-list: iframe verso host non-whitelisted rimossi
- HTTPS forzato su tutti gli URL

### Comando resanitize

php artisan articles:resanitize --dry-run
php artisan articles:resanitize --force
php artisan articles:resanitize --id=42 --force

### Configurazione

api/config/embeds.php:
- enabled: attiva/disattiva feature
- cache.ttl: TTL cache oEmbed (default 24h)
- rate_limit.max_attempts: rate limit (default 60/min)
- providers.*: pattern URL + iframe host
- sanitize.allowed_tags: tag HTML permessi
- sanitize.blocked_iframe_hosts: host iframe bloccati

---

## 🖍️ Sistema Highlight (NYT-level)

### Cosa fa
Gli utenti possono **evidenziare frasi** degli articoli, aggiungere note personali, reagire con emoji, e ritrovare tutto nella pagina `/highlight`.

### Feature

| Feature | Stato |
|---------|-------|
| Selezione testo → popup | ✅ |
| 3 colori (giallo, verde, rosa) | ✅ |
| Note personali | ✅ |
| Toggle privato/pubblico | ✅ |
| Reazioni emoji (5) | ✅ |
| Badge "X persone hanno reagito" | ✅ |
| Pagina `/highlight` personale | ✅ |
| SVG overlay (non tocca HTML) | ✅ |
| Fuzzy match (articolo modificato) | ✅ |
| Rilevamento orfani | ✅ |
| Coda offline | ✅ |
| Toast con undo | ✅ |

### Configurazione
`api/config/highlights.php`:
- `enabled`: kill switch
- `privacy.default_public`: default privacy
- `privacy.allow_public`: se false, utenti non possono rendere pubblici
- `limits.*`: lunghezza max, rate limit
- `colors`: 3 colori con label + hex
- `emojis`: emoji supportate
- `features`: toggle per reazioni/note/pubbliche/heatmap/share

### API

| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/api/highlights/config` | GET | Config pubblica |
| `/api/reader/register` | POST | Registra reader anonimo |
| `/api/reader/me` | GET | Info reader |
| `/api/highlights` | POST | Crea highlight |
| `/api/highlights/mine` | GET | I miei highlight |
| `/api/highlights/{id}` | PATCH/DELETE | Modifica/elimina |
| `/api/articles/{slug}/highlights` | GET | Highlight articolo |
| `/api/articles/{slug}/highlights/stats` | GET | Stats + heatmap |
| `/api/highlights/{id}/react` | POST/DELETE | Toggle reazione |

### Identità device-based
- Token anonimo generato al primo highlight
- Salvato in `localStorage` (`themedge_reader_token`)
- Header `X-Reader-Token` per autenticazione
- **Nessun account richiesto**
- ⚠️ Non sincronizzato cross-device (OAuth in roadmap)

### Privacy
- Default privato (opt-in pubblico)
- Toggle "Solo tu" / "Visibile a tutti"
- GDPR-safe: nessun dato personale pubblico

---

## 🖍️ Sistema Highlight — Livello 3 completo

### Feature utente
| Feature | Stato |
|---------|-------|
| Selezione testo → popup | ✅ |
| 3 colori (giallo, verde, rosa) | ✅ |
| Note personali | ✅ |
| Toggle privato/pubblico | ✅ |
| Reazioni emoji (una per reader, immutabile) | ✅ |
| Badge "X persone hanno reagito" | ✅ |
| Pagina `/highlight` personale | ✅ |
| Heatmap articolo con top fragments | ✅ |
| Condivisione come immagine PNG | ✅ |
| Web Share API nativo | ✅ |

### Feature amministrative
| Feature | Stato |
|---------|-------|
| Filament Resource `/admin/highlights` | ✅ |
| Moderazione (nascondi, ban reader) | ✅ |
| Widget KPI highlights | ✅ |
| Filtri (colore, note, reazioni, data) | ✅ |
| Bulk actions | ✅ |
| Policy editoriali a 3 livelli | ✅ |

### GDPR
| Feature | Stato |
|---------|-------|
| Export dati (Art. 15/20) | ✅ |
| Delete account (Art. 17) | ✅ |
| Pagina `/privacy/dati-personali` | ✅ |

### Regole
- **Una reazione per reader, immutabile**: non si può cambiare né rimuovere
- **Contatore = persone uniche**: "4 reazioni = 4 persone"
- **Privacy default: privato** — opt-in per pubblico
- **Policy a 3 livelli**: articolo > categoria > autore > default
