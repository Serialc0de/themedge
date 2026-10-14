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
            $content = mb_substr($result['_content'] ?? $result['excerpt'], 0, 400);
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
}
