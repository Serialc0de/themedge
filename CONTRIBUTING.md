# 🤝 Contributing — Themedge

Guida per contribuire al progetto Themedge.

**Ultimo aggiornamento:** 14 ottobre 2026

---

## 📑 Indice

1. [Filosofia del progetto](#filosofia-del-progetto)
2. [Come contribuire](#come-contribuire)
3. [Setup sviluppo](#setup-sviluppo)
4. [Workflow modifica](#workflow-modifica)
5. [Convenzioni di codice](#convenzioni-di-codice)
6. [Pattern obbligatori](#pattern-obbligatori)
7. [Convenzioni Git](#convenzioni-git)
8. [Test](#test)
9. [Segnalazione bug](#segnalazione-bug)
10. [Regole di sicurezza](#regole-di-sicurezza)

---

## Filosofia del progetto

**Themedge** è un giornale online editoriale con:

- **Qualità editoriale**: contenuto curato, non clickbait
- **Trasparenza**: fonti verificabili, correzioni pubbliche, disclosure AI
- **Innovazione tecnica**: AI locale (no cloud), Knowledge Engine unico
- **Privacy**: zero tracking invasivo, GDPR compliant
- **Zero costi ricorrenti**: tutto self-hosted (Ollama, Piper, Meilisearch)

**Principi:**

1. **Semplicità** > complessità
2. **Performance** > feature
3. **Privacy** > convenienza
4. **Test manuale** > "lo faccio dopo"
5. **Documentazione** > "si capisce dal codice"

---

## Come contribuire

### Cosa cerchiamo

- 🐛 **Bug fix** (con test)
- ✨ **Feature** (con design documentato)
- 📚 **Documentazione** (chiara, concisa)
- 🎨 **UX/UI** miglioramenti
- ⚡ **Performance** ottimizzazioni
- 🔒 **Security** fix
- 🌍 **i18n** (nuove lingue)

### Cosa NON cerchiamo

- ❌ Refactoring senza motivo
- ❌ Cambio di stack/framework
- ❌ Feature "nice to have" senza uso
- ❌ Dipendenze pesanti (preferiamo codice custom)
- ❌ Tracking/Analytics invasivi

---

## Setup sviluppo

### 1. Fork + clone

```bash
# Fork su GitHub, poi:
git clone https://github.com/TUO-USERNAME/themedge.git
cd themedge

# Aggiungi upstream
git remote add upstream https://github.com/Serialc0de/themedge.git
```

### 2. Setup backend

```bash
cd api

composer install
cp .env.example .env
php artisan key:generate

# Configura .env (DB, Redis, Meilisearch, Ollama)
nano .env

php artisan migrate --seed
php artisan storage:link
php artisan make:filament-user
```

### 3. Setup frontend

```bash
cd ../web
npm install
cp .env.example .env.local  # se esiste
nano .env.local
```

### 4. Setup servizi

**Ollama:**

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull qwen2.5:3b
ollama pull nomic-embed-text
```

**Piper TTS:**

```bash
pipx install piper-tts
# Scarica modello italiano (vedi SETUP.md)
```

**Meilisearch:**

```bash
curl -L https://install.meilisearch.com | sh
```

### 5. Avvio

```bash
# Terminale 1 — Backend
cd api
php artisan serve

# Terminale 2 — Queue
cd api
php artisan queue:work

# Terminale 3 — Frontend
cd web
npm run dev
```

---

## Workflow modifica

### ⚠️ REGOLA CRITICA

**Non modificare MAI direttamente il codice in produzione** (`/www/wwwroot/themedge.com/`).

**Il workflow corretto è:**

```
Modifica in LOCALE → Test → Commit → Push → Deploy
```

### Workflow in locale

```bash
# 1. Aggiorna da upstream
git checkout main
git pull upstream main

# 2. Crea branch
git checkout -b feature/nome-feature

# 3. Modifica codice
nano api/app/Models/Article.php
nano web/components/ArticleBody.tsx

# 4. Verifica sintassi
php -l api/app/Models/Article.php
cd web && npx tsc --noEmit --project tsconfig.json

# 5. Test manuale
# Apri http://localhost:3000

# 6. Commit
git add .
git commit -m "feat(article): aggiungi campo reading_time"

# 7. Push al tuo fork
git push origin feature/nome-feature

# 8. Apri Pull Request su GitHub
```

### Deploy in produzione (solo per admin)

**Vedi [DEPLOY.md](DEPLOY.md).**

---

## Convenzioni di codice

### PHP (Laravel)

**PSR-12** + convenzioni Laravel.

```php
<?php

namespace App\Services;

use App\Models\Article;

class ArticleService
{
    public function __construct(
        protected Summarizer $summarizer
    ) {}

    public function process(Article $article): string
    {
        $content = $article->content ?? '';
        return $this->summarizer->summarize($content);
    }
}
```

**Regole:**

- **Naming**: `camelCase` per metodi, `snake_case` per variabili DB
- **Type hints**: sempre
- **Return types**: sempre
- **Docblocks**: solo per metodi complessi
- **Imports**: ordinati alfabeticamente
- **Spazi**: 4 spazi indentazione (no tab)

**Naming convenzioni Laravel:**

- Modello: `Article` (singolare, PascalCase)
- Tabella: `articles` (plurale, snake_case)
- Controller: `ArticleController`
- Migration: `2026_10_14_120000_create_articles_table.php`
- Job: `GenerateArticleAudio`
- Event: `ArticlePublished`
- Listener: `SendArticleNotification`

### TypeScript / React

**Standard Next.js 16 + React 19.**

```tsx
'use client';

import { useState, useEffect } from 'react';
import type { Article } from '@/lib/types';

type Props = {
  article: Article;
  onSave?: (id: number) => void;
};

export function ArticleCard({ article, onSave }: Props) {
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    // ...
  }, []);

  return <div onClick={() => onSave?.(article.id)}>{article.title}</div>;
}
```

**Regole:**

- **Naming**: `PascalCase` per componenti, `camelCase` per variabili/funzioni
- **File**: `PascalCase.tsx` per componenti, `kebab-case.ts` per utility
- **Tipi**: sempre espliciti (no `any`)
- **Props**: `type Props = {...}` (no `interface` se non serve)
- **'use client'**: solo quando serve
- **Hooks**: `useState`, `useEffect`, `useCallback` in quest'ordine
- **Imports**: assoluti (`@/components/...`)

### Blade (Filament)

Segui le convenzioni Filament 3.

### CSS (Tailwind)

**Tailwind v4** + classi utility.

```tsx
<div className="flex items-center justify-between gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-900">
  <h2 className="font-serif text-xl font-bold text-gray-900 dark:text-gray-100">
    Titolo
  </h2>
</div>
```

**Regole:**

- **Ordinamento**: layout → spacing → colori → tipografia → effetti
- **Dark mode**: sempre `dark:` variant
- **Responsive**: mobile-first (`sm:`, `md:`, `lg:`)
- **Custom**: usa CSS modules solo se strettamente necessario

---

## Pattern obbligatori

### 1. React SSR-safe (NO React #418)

**❌ MAI:**

```tsx
const isMobile = typeof window !== 'undefined' && window.innerWidth < 768;
const token = localStorage.getItem('token');
```

**✅ SEMPRE:**

```tsx
const [mounted, setMounted] = useState(false);
useEffect(() => setMounted(true), []);

const isMobile = mounted && window.innerWidth < 768;
const token = mounted ? localStorage.getItem('token') : null;
```

### 2. Auto-embed URL (no iframe dentro `<p>`)

```php
// ❌ MAI: iframe dentro <p>
$content = preg_replace('/URL/', '<iframe ...>', $content);

// ✅ SEMPRE: chiudi </p>, metti iframe in <figure>, riapri <p>
$content = preg_replace_callback('/URL/', function ($m) {
    return '</p><figure><iframe ...></iframe></figure><p>';
}, $content);
```

### 3. HTML Sanitization

```php
// ❌ MAI: HTML raw al frontend
return $article->content;

// ✅ SEMPRE: sanitizza
return $this->sanitizer->sanitize($article->content);
```

```tsx
// ❌ MAI
<div dangerouslySetInnerHTML={{ __html: html }} />

// ✅ SEMPRE
<div dangerouslySetInnerHTML={{ __html: sanitizeHtml(html) }} />
```

### 4. Tinker inline (NO)

```bash
# ❌ MAI
php artisan tinker --execute="\$x = ...\x27..."
```

```bash
# ✅ SEMPRE: file PHP temporaneo
cat > /tmp/script.php << 'PHPEOF'
<?php
require __DIR__ . '/../../www/wwwroot/themedge.com/api/vendor/autoload.php';
$app = require_once __DIR__ . '/../../www/wwwroot/themedge.com/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// codice
PHPEOF
php /tmp/script.php
```

### 5. Migration timestamp

```bash
# ❌ MAI: stesso timestamp
TS=$(date +%Y_%m_%d_%H%M%S)
# due migration con stesso TS
```

```bash
# ✅ SEMPRE: timestamp progressivi
TS1="2026_10_14_120000"
TS2="2026_10_14_120001"
# oppure sleep 1 tra due date
```

### 6. Chown dopo modifiche root

```bash
# ❌ MAI
cat > app/Models/Article.php << 'EOF'
# file diventa root:root
EOF
```

```bash
# ✅ SEMPRE: fix owner dopo
cat > app/Models/Article.php << 'EOF'
...
EOF
chown -R www:www app/
```

### 7. cp -r con path

```bash
# ❌ MAI
cp -r /src/ /dest/   # se /dest/src/ esiste → /dest/src/src/
```

```bash
# ✅ SEMPRE: verifica
ls -la /dest/
cp -r /src/ /dest/
find /dest -type d | head -10  # verifica no duplicati
```

### 8. Cron

```bash
# ❌ MAI: sovrascrive
echo "..." | crontab -
```

```bash
# ✅ SEMPRE: edit + backup
crontab -l > /root/crontab.backup.$(date +%s)
crontab -e
```

### 9. Sed su JSX/TSX

```bash
# ❌ MAI: rompe la sintassi JSX
sed -i 's/old/new/' file.tsx
```

```bash
# ✅ SEMPRE: nano a mano o cat > file
nano file.tsx
# oppure
cat > file.tsx << 'TSXEOF'
...file completo...
TSXEOF
```

### 10. Next.js build

```bash
# ❌ MAI: solo restart
pm2 restart themedge-web
```

```bash
# ✅ SEMPRE: build + restart
cd /www/wwwroot/themedge.com/web
npm run build
pm2 restart themedge-web --update-env
```

---

## Convenzioni Git

### Formato commit

**Conventional Commits:**

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

**Types:**

- `feat`: nuova feature
- `fix`: bug fix
- `docs`: documentazione
- `style`: formattazione (no logica)
- `refactor`: refactoring (no feature/fix)
- `perf`: performance
- `test`: test
- `chore`: manutenzione (build, deps, ecc.)
- `ci`: CI/CD

**Scopes:**

- `article`, `wiki`, `ask`, `tts`, `highlight`, `admin`, `api`, `web`
- `embed`, `push`, `comment`, `analytics`
- `rag`, `ollama`, `piper`

**Esempi:**

```
feat(wiki): aggiungi pagina categoria
fix(ask): risolvi errore streaming SSE
docs(handoff): aggiorna con Knowledge Engine
perf(api): ottimizza query articoli
chore(deps): aggiorna next a 16.4.1
refactor(article): estrai logica sanitizzazione
```

### Branch naming

```
feature/nome-feature
fix/nome-bug
docs/nome-doc
refactor/nome-refactor
hotfix/nome-hotfix
```

### Pull Request

**Titolo:** come un commit.

**Descrizione:**

```markdown
## Cosa
Breve descrizione.

## Perché
Motivazione.

## Come testare
1. Passo 1
2. Passo 2

## Screenshot
[se UI]

## Checklist
- [ ] Test passano
- [ ] TypeScript check OK
- [ ] Documentazione aggiornata
- [ ] Nessun `console.log` residuo
- [ ] Nessun `.bak` aggiunto
```

---

## Test

### Backend

```bash
cd api

# Test (Pest)
php artisan test

# Test singolo
php artisan test --filter=ArticleTest

# Test con coverage
php artisan test --coverage
```

### Frontend

```bash
cd web

# TypeScript
npx tsc --noEmit --project tsconfig.json

# Lint
npm run lint

# Build di test
npm run build
```

### Manuale

**Checklist test manuale:**

- [ ] Sito pubblico carica
- [ ] Admin carica
- [ ] Login funziona
- [ ] Creazione articolo
- [ ] Pubblicazione
- [ ] Wiki generation
- [ ] RAG query
- [ ] TTS audio
- [ ] No React #418 (F12 console)
- [ ] No error 500 (F12 network)
- [ ] Mobile responsive
- [ ] Dark mode

---

## Segnalazione bug

### Template issue

```markdown
**Descrizione**
Cosa succede.

**Passi per riprodurre**
1. Vai su...
2. Clicca...
3. Vedi...

**Comportamento atteso**
Cosa dovrebbe succedere.

**Ambiente**
- OS: Ubuntu 26.04
- Browser: Chrome 130
- PHP: 8.4
- Node: 22.23.3

**Log**
```
[incolla errori]
```

**Screenshot**
[se applicabile]
```

### Cosa NON fare

- ❌ "Non funziona" (troppo vago)
- ❌ Screenshot senza testo
- ❌ Issue duplicate (cerca prima)
- ❌ Issue per feature richieste (usa Discussions)

---

## Regole di sicurezza

### ❌ MAI committare

- `.env` (con credenziali)
- `.env.local`
- Chiavi API
- Password
- Token GitHub
- Certificati SSL
- File audio generati
- Log

### ✅ SEMPRE

- Usa `.env.example` per template
- Sanitizza input/output
- Valida con FormRequest
- Usa HTTPS in produzione
- Rate limiting su endpoint pubblici
- CSRF tokens su form
- Prepara migration con `--seed` (no data reali)

### Segnalazione vulnerabilità

**NON aprire issue pubblica** per vulnerabilità. Invia email a:

```
security@themedge.com
```

Vedi [SECURITY.md](../SECURITY.md).

---

## Domande?

- **Bug**: apri Issue
- **Feature**: apri Discussion
- **Domande**: apri Discussion
- **Sicurezza**: email privata

---

## Licenza

Contribuendo, accetti che il tuo codice sia rilasciato sotto la stessa licenza del progetto (vedi [LICENSE](../LICENSE)).

---

**Grazie per contribuire a Themedge!** 🚀

---

*Ultima modifica: 14 ottobre 2026*
