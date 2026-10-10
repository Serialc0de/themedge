# 🤝 Handoff — Progetto Themedge

Documento di passaggio per nuovo operatore.

## 🚨 Regola PHP-FPM (LEGGERE PRIMA)

**MAI** usare `systemctl restart php-fpm-84` → crash loop garantito.

**SEMPRE** usare:
\`\`\`bash
/root/fpm.sh
\`\`\`

## 📊 Stato progetto

Vedi sezione "COSA FUNZIONA" nel messaggio di handoff.

## 🔑 Credenziali

Sono nei file del server (NON in questo repo):
- \`/root/.my.cnf\` — MySQL
- \`/root/.laravel_db_password\` — DB Laravel
- \`/www/server/redis/redis.conf\` — Redis password
- \`/root/.meilisearch_master_key\` — Meilisearch
- \`/root/.github-token\` — GitHub token
- \`/www/wwwroot/themedge.com/api/.env\` — Laravel APP_KEY

## 🎯 Prossimi passi

1. Notifiche email workflow
2. SEO avanzato
3. Versioning visivo
4. Analytics

## 📚 Documentazione completa

Vedi [docs/](./docs/) nella repo.
