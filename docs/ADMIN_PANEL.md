# 🎛️ Pannello Admin — Guida Completa

Documentazione delle funzionalità del pannello Filament.

**URL:** https://admin.themedge.com/admin
**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Dashboard](#dashboard)
2. [Utenti](#utenti)
3. [Articoli](#articoli)
4. [Wiki Editoriale (NUOVO)](#wiki-editoriale-nuovo)
5. [Chiedi a Themedge — Analytics (NUOVO)](#chiedi-a-themedge--analytics-nuovo)
6. [Media Library](#media-library)
7. [Audit Log](#audit-log)
8. [Redirect Manager](#redirect-manager)
9. [Shield — Ruoli e Permessi](#shield--ruoli-e-permessi)
10. [Stato Sistema](#stato-sistema)
11. [Sicurezza](#sicurezza)
12. [Personalizzazioni](#personalizzazioni)
13. [Note di sviluppo](#note-di-sviluppo)

---

## 📊 Dashboard

La dashboard principale mostra:

### KPI (card in alto)

- **Articoli totali** — con breakdown pubblicati/bozze
- **Visualizzazioni totali** — somma di tutte le views
- **In review** — articoli in attesa di revisione
- **Autori attivi** — utenti con `is_active = true`

### Widget (16)

1. **Grafico pubblicazioni** (ApexCharts) — 30 giorni, area chart
2. **Top 5 articoli** — più letti
3. **Top 5 autori** — più prolifici
4. **Articoli in review** — con azioni rapide (Approva/Rifiuta)
5. **Stato sistema** — MySQL, Redis, Meilisearch, Storage, Cache, Coda
6. **Media recenti** — ultime 12 immagini
7. **Ultime attività** — ultimi 10 eventi (audit log)
8. **Redirect più usati** — top 5
9. **Editorial KPI** — statistiche editoriali
10. **Breaking News attive**
11. **Commenti in attesa** — con moderazione rapida
12. **Articoli in revisione** — pending review
13. **Highlight stats**
14. **Push stats** — iscritti + invii
15. **Top redirects**
16. **Ask Stats Overview** (NUOVO) — query AI, % feedback positivi, tempo medio

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

- **Impersona** — accedi come l'utente (non su te stesso)
- **Attiva/Disattiva** — toggle rapido (non su te stesso)
- **Reset password** — da Edit utente

### Filtri

- Ruolo
- Stato account (attivo/disattivato)

---

## 📝 Articoli (/admin/articles)

### Stati disponibili

| Stato | Emoji | Colore |
|-------|-------|--------|
| draft | 📝 | Grigio |
| review | ⏳ | Giallo |
| approved | ✅ | Blu |
| scheduled | 🕒 | Blu |
| published | 🚀 | Verde |
| archived | 📦 | Rosso |

### Azioni workflow

| Azione | Visibile per | Cosa fa |
|--------|--------------|---------|
| Invia in review | draft + autore/editor | draft → review |
| Approva | review + editor | review → approved |
| Rifiuta | review + editor | review → draft + motivo |
| Pubblica | draft/approved/scheduled + editor | → published |
| Archivia | published + editor | → archived |

### Azioni AI (NUOVO)

| Azione | Cosa fa |
|--------|---------|
| ✨ **Genera TL;DR** | Riassunto + key points con Ollama |
| 🔍 **Rileva entità** | Estrae person, org, location, event, concept |
| 🎯 **Ottimizza SEO (AI)** | Genera meta title, description, keywords, hashtags |
| ✅ **Fact-check automatico** | Estrae claim verificabili |
| 🎙️ **Genera audio** | Crea MP3 con Piper TTS |

### Colonna Audio (NUOVO)

Mostra lo stato del TTS:

| Badge | Significato |
|-------|-------------|
| ✅ 2:54 | Audio pronto, durata |
| ⏳ in corso | Generazione in corso |
| ❌ errore | Generazione fallita |
| — | Nessun audio |

### Editor SEO (sezione collassata)

5 check automatici:

- Meta title: 40-60 caratteri
- Meta description: 120-160 caratteri
- Contenuto: min 300 parole
- Slug: max 60 caratteri
- Immagine di copertina: presente

**Anteprima Google** — come apparirà nei risultati

**Pulsante Anteprima frontend** — apre l'URL pubblico

### Sezioni (tabs)

- **Contenuto** (title, slug, excerpt, content)
- **Scrollytelling** (11 tipi di sezione)
- **Entità** (auto-detection AI)
- **Fonti** (verificabili)
- **Timeline** (contesto storico)
- **Fact-check** (claim verificati)
- **Knowledge Box** ("Cosa sappiamo / non sappiamo")
- **Correzioni** (pubbliche)
- **Curate** (approfondimenti correlati)
- **SEO** (meta + AI suggestions)
- **AI Summary** (TL;DR)
- **Audio** (TTS + player preview)
- **AI Disclosure** (trasparenza uso AI)
- **Policy** (highlight policy)

### Filtri

- Stato (multi-select)
- Autore (solo editor/admin)
- Categoria
- Solo in evidenza
- Solo i miei (per author/contributor)

### Permessi per ruolo

| Ruolo | Vede | Può |
|-------|------|-----|
| super_admin | Tutti | Tutto |
| editor | Tutti | Modifica, approva, pubblica, archivia, elimina |
| author | Solo i suoi | Crea, modifica bozze, invia in review |
| contributor | Solo i suoi | Crea bozze |

---

## 📚 Wiki Editoriale (NUOVO)

**URL:** `/admin/wiki-pages`

Enciclopedia viva auto-generata dagli articoli.

### Lista pagine

**Colonne:**

- Icona (emoji)
- Titolo + subtitle
- Tipo (badge colorato)
- Stato (badge: draft/published/archived)
- Articoli (badge contatore)
- AI (icona sparkles se generata)
- Viste
- Aggiornata

**Filtri:**

- Tipo (person, organization, location, event, concept, topic)
- Stato (draft, published, archived)
- Generata da AI
- Soft deleted

**Azioni per riga:**

- 👁️ **Vedi sito** — apre `/wiki/slug`
- ✅ **Pubblica** — cambia stato
- ✨ **Rigenera AI** — dispatch job
- ✏️ **Edit**
- 🗑️ **Delete**

**Bulk actions:**

- Pubblica tutte
- Delete/Restore/ForceDelete

### Header actions

- ✨ **Genera da entità** — form con tipo + limit
- ➕ **Nuova pagina**

### Form (Edit)

**Sezioni:**

1. **Identità**
   - Titolo (auto-slug)
   - Slug
   - Sottotitolo (es. "Papa 2025–")
   - Tipo (6 opzioni)
   - Stato (3 opzioni)
   - Icona (emoji)
   - Data pubblicazione

2. **Contenuto**
   - Sommario (textarea)
   - Content (RichEditor)
   - Infobox (KeyValue)
   - Alias (TagsInput)

3. **Media**
   - Featured image (FileUpload)
   - Didascalia

4. **SEO**
   - Meta title
   - Meta description

5. **Info AI & Sistema**
   - Generato da AI (placeholder)
   - Statistiche (placeholder)
   - Entità collegata (Select)

### Auto-generazione

**Da admin:**

1. Clic "Genera da entità"
2. Seleziona tipo (opzionale)
3. Seleziona limit (default 10)
4. Clic "Genera"
5. Job in coda → ~2 min per wiki

**Da terminale:**

```bash
cd /www/wwwroot/themedge.com/api
php artisan wiki:generate --limit=30
php artisan wiki:generate --type=person --limit=10
php artisan wiki:generate --entity=5 --sync
php artisan wiki:generate --force  # rigenera esistenti
```

### Flusso consigliato

1. **Genera** wiki da entità
2. **Controlla** 3-4 wiki (qualità AI)
3. **Modifica** manualmente (aggiungi contesto, correggi)
4. **Pubblica**
5. **Monitora** analytics

---

## 💬 Chiedi a Themedge — Analytics (NUOVO)

**URL:** `/admin/ask-queries`

Dashboard delle query AI.

### Widget (AskStatsOverview)

3 card:

1. **Query totali** — con breakdown ultime 24h
2. **Feedback positivi %** — con breakdown 👍/👎
3. **Tempo medio risposta** — in secondi

### Tabella

**Colonne:**

- ID
- Domanda (searchable)
- Fonti (badge contatore)
- Feedback (👍 N | 👎 N)
- Tempo (in secondi)
- IP (nascosto di default)
- Data

**Filtri:**

- Con feedback
- Con feedback negativo
- Ultime 24h
- Ultimi 7 giorni

**Azioni:**

- 👁️ **View** — dettaglio

### Vista dettaglio

**Sezioni:**

1. **Domanda** — testo + data, IP, sessione, modello, tempi
2. **Risposta** — markdown render
3. **Fonti** — lista con score % match
4. **Feedback** — voti + commenti

### Analytics utili

- **Query più frequenti** — cosa cerca il pubblico
- **Query senza fonti** — argomenti mancanti
- **Feedback negativi** — dove migliorare
- **Tempo medio** — monitorare performance

### Pulizia automatica

Cron domenica 4:00 → cancella query > 90 giorni.

---

## 🖼️ Media Library (/admin/media)

### Funzionalità

- Upload multiplo con drag and drop (fino a 20 file)
- Crop inline (immagini)
- Azione **URL** — mostra URL completo in modal
- Azioni: Apri, Modifica, Elimina

### Modello MediaAsset

Tabella dedicata per la libreria media standalone.

Ogni media ha:

- `title`
- `description`
- Collezione `library` (Spatie Media Library)

### Storage

- **Disk:** `public`
- **Path:** `storage/app/public`
- **Symlink:** `public/storage`
- **MIME:** jpg, jpeg, png, webp, gif, svg, pdf
- **Max size:** 5 MB

---

## 📋 Audit Log (/admin/activities)

### Cosa traccia

- **Article** — modifiche a titolo, slug, excerpt, contenuto, stato, ecc.
- **User** — modifiche a nome, email, is_active, job_title
- **Category** — modifiche a nome, slug, descrizione, colore
- **Tag** — modifiche a nome, slug
- **Redirect** — modifiche a from, to, status_code, is_active
- **Comment** — moderazione
- **Media** — modifiche a title, description
- **Wiki** (NUOVO) — modifiche a title, status, summary, content

### Filtri

- Tipo (Article/User/Category/Tag/Redirect/Wiki)
- Evento (created/updated/deleted/restored)
- Solo oggi
- Ultima settimana

### Azione

- **Dettagli** — modal con diff old/new value

---

## 🔀 Redirect Manager (/admin/redirects)

### Funzionalità

- CRUD completo redirect
- Codici supportati: 301, 302, 307, 308
- Contatore hit + ultimo hit
- Stato attivo/disattivo

### Azioni

- **Test** — apre URL sorgente in nuova tab
- **Reset hits** — azzera contatore
- Modifica, Elimina

### Auto-redirect

Quando modifichi lo slug di un articolo pubblicato, viene automaticamente creato un redirect 301 dallo slug vecchio al nuovo.

### Middleware HandleRedirects

Registrato globalmente in `bootstrap/app.php`.

Agisce solo se la risposta è 404:

1. Cerca il path nella tabella redirects
2. Se trovato, incrementa hit e redirige

---

## 🛡️ Shield — Ruoli e Permessi

### Ruoli predefiniti

| Ruolo | Permessi |
|-------|----------|
| super_admin | 55 (tutti) |
| editor | 36 |
| author | 10 |
| contributor | 4 |

### Cosa può fare ogni ruolo

| Azione | super_admin | editor | author | contributor |
|--------|-------------|--------|--------|-------------|
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
| **Gestisce Wiki** (NUOVO) | Sì | Sì | No | No |
| **Vede Ask Analytics** (NUOVO) | Sì | Sì | No | No |

---

## ⚙️ Stato Sistema

Widget **Stato sistema** mostra in tempo reale:

- **MySQL** — connessione al database
- **Redis** — ping al server Redis
- **Meilisearch** — endpoint /health
- **Storage** — permessi scrittura su storage/app/public
- **Cache** — sempre OK
- **Coda** — sempre OK

**Polling:** ogni 60 secondi.

### Verifica manuale

```bash
# MySQL
mysql -e "SELECT 1"

# Redis
redis-cli ping

# Meilisearch
curl -s http://localhost:7700/health

# Storage
ls -la /www/wwwroot/themedge.com/api/storage/app/public/

# Coda
pm2 list | grep queue
```

---

## 🔐 Sicurezza

- Autenticazione Filament nativa
- 2FA disponibile via Breezy
- Sessioni su file (`storage/framework/sessions`)
- Password hashate (bcrypt)
- CSRF protezione automatica
- Middleware `canAccessPanel` — solo utenti `is_active = true`
- **Rate limiting** su login (5/min)
- **Audit log** su tutte le modifiche

---

## 🎨 Personalizzazioni

### Colori

Configurati in `app/Providers/Filament/AdminPanelProvider.php`:

```php
colors(['primary' => Color::Amber])
```

### Auto-discovery

Filament scopre automaticamente:

- **Resources** in `app/Filament/Resources/`
- **Pages** in `app/Filament/Pages/`
- **Widgets** in `app/Filament/Widgets/`

### Group menu (aggiornato)

- **Contenuti**: Articoli, Categorie, Tag, Media
- **Redazione**: Utenti, Editorial Roles
- **Trust**: Fact-checks, Correzioni, Fonti, Glossario, Timeline, Knowledge
- **Knowledge** (NUOVO): Wiki Pages
- **AI** (NUOVO): Ask Queries
- **SEO**: Redirect, SEO Settings, Robots, Sitemap, Breaking
- **Sistema**: Audit Log, Analytics
- **Shield**: Ruoli

---

## 📝 Note di sviluppo

### Modificare un Resource

I file sono in `app/Filament/Resources/`.

Dopo la modifica:

```bash
cd /www/wwwroot/themedge.com/api

php -l app/Filament/Resources/NomeResource.php
chown -R www:www app/Filament/
php artisan optimize:clear
php artisan filament:optimize-clear
[FPM_SCRIPT]
```

### Aggiungere un Widget

```bash
php artisan make:filament-widget NomeWidget --panel=admin
```

Il widget viene automaticamente scoperto.

### Aggiungere un Ruolo

1. Vai su `/admin/shield/roles`
2. Clicca "New role"
3. Seleziona permessi
4. Salva

### Aggiungere una Resource

```bash
php artisan make:filament-resource NomeModel --generate
```

Poi modifica il file generato.

### Debug errori

```bash
tail -50 storage/logs/laravel.log | grep -i error
```

### Timeout page (500 su dettaglio)

Se una pagina di dettaglio (View) va in timeout, rimuovi `ViewAction` o semplifica l'`infolist`:

```php
Tables\Actions\ViewAction::make(),  // rimuovi questa
```

---

## 📚 Vedi anche

- [HANDOFF.md](../HANDOFF.md) — Panoramica progetto
- [FEATURES.md](FEATURES.md) — Tutte le feature
- [WIKI.md](WIKI.md) — Wiki editoriale completa
- [RAG.md](RAG.md) — AI Chat + Knowledge Engine
- [OLLAMA.md](OLLAMA.md) — Configurazione AI
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) — Problemi comuni

---

*Ultima modifica: 14 ottobre 2026*
