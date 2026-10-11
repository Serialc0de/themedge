<?php

namespace App\Services\EmbedProviders;

class SpotifyProvider extends AbstractProvider
{
    public function name(): string { return 'Spotify'; }
    public function aspect(): string { return 'auto'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match('#^https?://open\.spotify\.com/#i', $url);
    }

    public function resolve(string $url): ?string
    {
        if (!preg_match('#open\.spotify\.com/(track|album|playlist|episode|show|artist)/([a-zA-Z0-9]+)#', $url, $m)) {
            return null;
        }
        $type = $m[1];
        $id = $m[2];
        $src = "https://open.spotify.com/embed/{$type}/{$id}";

        $height = match($type) {
            'track', 'episode' => 152,
            'album', 'artist' => 352,
            'playlist', 'show' => 380,
            default => 352,
        };

        return '<iframe src="' . $src . '" width="100%" height="' . $height . '" '
             . 'frameborder="0" allow="autoplay; clipboard-write; encrypted-media; '
             . 'fullscreen; picture-in-picture" loading="lazy" title="Spotify embed"></iframe>';
    }
}
