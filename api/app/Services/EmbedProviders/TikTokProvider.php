<?php

namespace App\Services\EmbedProviders;

class TikTokProvider extends AbstractProvider
{
    public function name(): string { return 'TikTok'; }
    public function aspect(): string { return '9/16'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?tiktok\.com/@[^/]+/video/\d+#i', $url);
    }

    public function resolve(string $url): ?string
    {
        return '<blockquote class="tiktok-embed" cite="' . htmlspecialchars($url, ENT_QUOTES) . '" '
             . 'data-video-id="">'
             . '<section><a target="_blank" href="' . htmlspecialchars($url, ENT_QUOTES) . '"></a></section>'
             . '</blockquote>';
    }
}
