<?php

namespace App\Services\Rag;

class RagPromptBuilder
{
    public function build(string $question, array $contextResults): string
    {
        if (empty($contextResults)) {
            return "Non ho informazioni su questo argomento negli archivi di Themedge. Rispondi brevemente che non puoi aiutare.\n\nDOMANDA: {$question}\n\nRISPOSTA:";
        }

        $context = '';
        foreach ($contextResults as $i => $result) {
            $num = $i + 1;
            $isArticle = ($result['kind'] ?? '') === 'article';
            $maxLen = $isArticle ? 800 : 400;
            $content = mb_substr($result['_content'] ?? $result['excerpt'], 0, $maxLen);
            $context .= "[{$num}] {$result['title']}: {$content}\n\n";
        }

        return <<<PROMPT
Rispondi in italiano basandoti SOLO sulle fonti. Cita con [1], [2]. Se non ci sono info, dillo.

FONTI:
{$context}

DOMANDA: {$question}

RISPOSTA:
PROMPT;
    }
    /**
     * Prompt specifico per Wiki Q&A (più direttivo).
     */
    public function buildForWiki(string $question, array $contextResults, string $wikiTitle): string
    {
        if (empty($contextResults)) {
            return "Non ho informazioni su questo argomento nella wiki \"{$wikiTitle}\".\n\nDOMANDA: {$question}\n\nRISPOSTA:";
        }

        $context = '';
        foreach ($contextResults as $i => $result) {
            $num = $i + 1;
            $kind = $result['kind'] ?? 'unknown';
            $kindLabel = match ($kind) {
                'wiki_chunk' => 'Wikipedia',
                'article' => 'Articolo Themedge',
                'wiki_infobox' => 'Scheda',
                default => 'Fonte',
            };
            $isArticle = ($result['kind'] ?? '') === 'article';
            $maxLen = $isArticle ? 800 : 400;
            $content = mb_substr($result['_content'] ?? $result['excerpt'], 0, $maxLen);
            $context .= "[{$num}] ({$kindLabel}) {$result['title']}: {$content}\n\n";
        }

        return <<<PROMPT
Sei un assistente che risponde a domande sulla voce wiki "{$wikiTitle}" di Themedge.

REGOLE:
1. Rispondi SOLO basandoti sulle fonti fornite
2. Se la risposta non è nelle fonti, dì: "Non ho questa informazione nella wiki"
3. Cita le fonti con [1], [2], ecc.
4. Sii conciso: 2-3 frasi
5. Non inventare fatti

FONTI:
{$context}
DOMANDA: {$question}

RISPOSTA:
PROMPT;
    }

}
