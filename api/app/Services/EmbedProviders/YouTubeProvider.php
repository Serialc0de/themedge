<?php

namespace App\Services\EmbedProviders;

class YouTubeProvider extends AbstractProvider
{
    public function name(): string { return 'YouTube'; }
    public function aspect(): string { return '16/9'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match(
            '#^https?://(www\.)?(youtube\.com/(watch\?v=|embed/|shorts/)|youtu\.be/)#i',
            $url
        );
    }

    public function resolve(string $url): ?string
    {
        $id = $this->extractId($url);
        if (!$id) return null;

        $src = 'https://www.youtube-nocookie.com/embed/' . $id;
        return '<iframe src="' . $src . '" width="560" height="315" '
             . 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; '
             . 'encrypted-media; gyroscope; picture-in-picture; web-share" '
             . 'allowfullscreen loading="lazy" title="YouTube video"></iframe>';
    }

    protected function extractId(string $url): ?string
    {
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m)) return $m[1];
        if (preg_match('#youtube\.com/(?:watch\?v=|embed/|shorts/)([a-zA-Z0-9_-]{11})#', $url, $m)) return $m[1];
        return null;
    }
}
