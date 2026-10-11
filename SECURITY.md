# 🔐 Security — Themedge

## Vulnerabilità note

### braces@3.0.3 — CVE-2026-93687 (HIGH)

**Stato:** 🟡 Accettata (devDependency, non sfruttabile in produzione)

**Dettagli:**
- CVE: GHSA-vfj7-8cjw-p6xm
- Severità: High
- Impattata: `braces@3.0.3` (via `eslint-config-next` → `@next/eslint-plugin-next` → `fast-glob` → `micromatch` → `braces`)
- Descrizione: stack overflow in pattern profondamente annidati

**Analisi del rischio:**
- ✅ `braces` è usata **solo** in fase di lint (ESLint) — **mai** in produzione
- ✅ Il sito in produzione esegue `next start`, che non carica ESLint
- ✅ Lo sfruttamento richiede accesso al repo (iniezione di pattern nel codice)
- ✅ Nessun fix upstream disponibile (ultima versione: `3.0.3`)
- ❌ `npm audit fix --force` peggiorerebbe la situazione (downgrade a `eslint-config-next@14.2.35`)

**Decisione:**
Ignorata fino a quando `braces@>=3.0.4` non sarà disponibile. Rivalutare periodicamente con `npm audit`.

**Ultima verifica:** 2026-10-11

---

## Policy

- **Report di sicurezza:** inviare a security@themedge.com
- **Audit dipendenze:** `npm audit` in CI (fallisce solo su `critical`)
- **Aggiornamenti:** Dependabot settimanale
