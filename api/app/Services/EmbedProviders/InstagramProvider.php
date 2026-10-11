<?php

namespace App\Services\EmbedProviders;

class InstagramProvider extends AbstractProvider
{
    public function name(): string { return 'Instagram'; }
    public function aspect(): string { return 'auto'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?instagram\.com/(p|reel|tv)/[a-zA-Z0-9_-]+#i', $url);
    }

    public function resolve(string $url): ?string
    {
        if (!preg_match('#instagram\.com/(p|reel|tv)/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return null;
        }
        $canonical = 'https://www.instagram.com/' . $m[1] . '/' . $m[2] . '/';
        return '<blockquote class="instagram-media" data-instgrm-permalink="' . $canonical . '" '
             . 'data-instgrm-version="14"></blockquote>';
    }
}
