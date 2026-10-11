<?php

namespace App\Services\EmbedProviders;

class GenericProvider extends AbstractProvider
{
    public function name(): string { return 'Generic'; }
    public function aspect(): string { return '16/9'; }

    public function matches(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    public function resolve(string $url): ?string
    {
        // Fallback: non risolviamo nulla di automatico. Il sanitizer
        // bloccherà iframe non in allow-list, quindi non generiamo HTML.
        return null;
    }
}
