# 🎛️ Pannello Admin — Guida Completa

Documentazione delle funzionalità del pannello Filament.

URL: admin.themedge.com/admin

---

## 📊 Dashboard

La dashboard principale mostra:

### KPI (card in alto)
- Articoli totali — con breakdown pubblicati/bozze
- Visualizzazioni totali — somma di tutte le views
- In review — articoli in attesa di revisione
- Autori attivi — utenti con is_active = true

### Widget
1. Grafico pubblicazioni (ApexCharts) — 30 giorni, area chart
2. Top 5 articoli — più letti
3. Top 5 autori — più prolifici
4. Articoli in review — con azioni rapide (Approva/Rifiuta)
5. Stato sistema — MySQL, Redis, Meilisearch, Storage, Cache, Coda
6. Media recenti — ultime 12 immagini
7. Ultime attività — ultimi 10 eventi (audit log)
8. Redirect più usati — top 5

---

## 👥 Utenti (/admin/users)

### Funzionalità
- CRUD completo utenti
- Avatar con crop inline
- Bio (appare sulla pagina autore)
- Ruoli (Shield): multipli per utente
- Job title (es. Caporedattore)
- Twitter handle
- Stato account (attivo/disattivo)

### Azioni
- Impersona — accedi come l'utente (non su te stesso)
- Attiva/Disattiva — toggle rapido (non su te stesso)
- Reset password — da Edit utente

### Filtri
- Ruolo
- Stato account (attivo/disattivato)

---

## 📝 Articoli (/admin/articles)

### Stati disponibili

| Stato | Emoji | Colore |
|---|---|---|
| draft | 📝 | Grigio |
| review | ⏳ | Giallo |
| approved | ✅ | Blu |
| scheduled | 🕒 | Blu |
| published | 🚀 | Verde |
| archived | 📦 | Rosso |

### Azioni workflow

| Azione | Visibile per | Cosa fa |
|---|---|---|
| Invia in review | draft + autore/editor | draft → review |
| Approva | review + editor | review → approved |
| Rifiuta | review + editor | review → draft + motivo |
| Pubblica | draft/approved/scheduled + editor | → published |
| Archivia | published + editor | → archived |

### Editor SEO (sezione collassata)

5 check automatici:
- Meta title: 40-60 caratteri
- Meta description: 120-160 caratteri
- Contenuto: min 300 parole
- Slug: max 60 caratteri
- Immagine di copertina: presente

Anteprima Google — come apparirà nei risultati

Pulsante Anteprima frontend — apre l'URL pubblico

### Filtri
- Stato (multi-select)
- Autore (solo editor/admin)
- Categoria
- Solo in evidenza
- Solo i miei (per author/contributor)

### Permessi per ruolo

| Ruolo | Vede | Può |
|---|---|---|
| super_admin | Tutti | Tutto |
| editor | Tutti | Modifica, approva, pubblica, archivia, elimina |
| author | Solo i suoi | Crea, modifica bozze, invia in review |
| contributor | Solo i suoi | Crea bozze |

---

## 🖼️ Media Library (/admin/media)

### Funzionalità
- Upload multiplo con drag and drop (fino a 20 file)
- Crop inline (immagini)
- Azione URL — mostra URL completo in modal
- Azioni: Apri, Modifica, Elimina

### Modello MediaAsset
Tabella dedicata per la libreria media standalone.
Ogni media ha:
- title
- description
- Collezione library (Spatie Media Library)

---

## 📋 Audit Log (/admin/activities)

### Cosa traccia
- Article — modifiche a titolo, slug, excerpt, contenuto, stato, ecc.
- User — modifiche a nome, email, is_active, job_title
- Category — modifiche a nome, slug, descrizione, colore
- Tag — modifiche a nome, slug
- Redirect — modifiche a from, to, status_code, is_active

### Filtri
- Tipo (Article/User/Category/Tag)
- Evento (created/updated/deleted/restored)
- Solo oggi
- Ultima settimana

### Azione
- Dettagli — modal con diff old/new value

---

## 🔀 Redirect Manager (/admin/redirects)

### Funzionalità
- CRUD completo redirect
- Codici supportati: 301, 302, 307, 308
- Contatore hit + ultimo hit
- Stato attivo/disattivo

### Azioni
- Test — apre URL sorgente in nuova tab
- Reset hits — azzera contatore
- Modifica, Elimina

### Auto-redirect
Quando modifichi lo slug di un articolo pubblicato, viene automaticamente creato un redirect 301 dallo slug vecchio al nuovo.

### Middleware HandleRedirects
Registrato globalmente in bootstrap/app.php.
Agisce solo se la risposta è 404:
1. Cerca il path nella tabella redirects
2. Se trovato, incrementa hit e redirige

---

## 🛡️ Shield — Ruoli e Permessi

### Ruoli predefiniti

| Ruolo | Permessi |
|---|---|
| super_admin | 55 (tutti) |
| editor | 36 |
| author | 10 |
| contributor | 4 |

### Cosa può fare ogni ruolo

| Azione | super_admin | editor | author | contributor |
|---|---|---|---|---|
| Vede tutti gli articoli | Sì | Sì | No | No |
| Crea articoli | Sì | Sì | Sì | Sì |
| Modifica articoli | Sì | Sì | solo i suoi | solo bozze proprie |
| Invia in review | Sì | Sì | Sì | Sì |
| Approva/Rifiuta | Sì | Sì | No | No |
| Pubblica | Sì | Sì | No | No |
| Elimina articoli | Sì | Sì | solo i suoi | No |
| Gestisce utenti | Sì | No | No | No |
| Gestisce ruoli | Sì | No | No | No |
| Gestisce redirect | Sì | Sì | No | No |

---

## ⚙️ Stato Sistema

Widget Stato sistema mostra in tempo reale:
- MySQL — connessione al database
- Redis — ping al server Redis
- Meilisearch — endpoint /health
- Storage — permessi scrittura su storage/app/public
- Cache — sempre OK
- Coda — sempre OK

Polling ogni 60 secondi.

---

## 🔐 Sicurezza

- Autenticazione Filament nativa
- 2FA disponibile via Breezy
- Sessioni su file (storage/framework/sessions)
- Password hashate (bcrypt)
- CSRF protezione automatica
- Middleware canAccessPanel — solo utenti is_active = true

---

## 🎨 Personalizzazioni

### Colori
Configurati in app/Providers/Filament/AdminPanelProvider.php:
colors(['primary' => Color::Amber])

### Auto-discovery
Filament scopre automaticamente:
- Resources in app/Filament/Resources/
- Pages in app/Filament/Pages/
- Widgets in app/Filament/Widgets/

### Group menu
- Contenuti: Articoli, Categorie, Tag, Media
- Redazione: Utenti
- SEO: Redirect
- Sistema: Audit Log
- Shield: Ruoli

---

## 📝 Note di sviluppo

### Modificare un Resource
I file sono in app/Filament/Resources/.
Dopo la modifica:
php artisan optimize:clear
php artisan filament:optimize-clear
/root/fpm.sh

### Aggiungere un Widget
php artisan make:filament-widget NomeWidget --panel=admin
Il widget viene automaticamente scoperto.

### Aggiungere un Ruolo
1. Vai su /admin/shield/roles
2. Clicca New role
3. Seleziona permessi
4. Salva
