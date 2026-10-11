<?php

namespace App\Services\EmbedProviders;

abstract class AbstractProvider
{
    /** Nome visualizzato */
    abstract public function name(): string;

    /** Se questo provider gestisce l'URL */
    abstract public function matches(string $url): bool;

    /**
     * Risolve l'URL in HTML embed (iframe/blockquote).
     * Ritorna null se non risolvibile.
     */
    abstract public function resolve(string $url): ?string;

    /** Aspect ratio CSS (es. '16/9'). 'auto' = nessuno */
    public function aspect(): string
    {
        return '16/9';
    }

    /** Helper: estrae host da URL */
    protected function host(string $url): string
    {
        return strtolower(parse_url($url, PHP_URL_HOST) ?? '');
    }
}
