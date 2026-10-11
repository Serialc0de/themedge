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
