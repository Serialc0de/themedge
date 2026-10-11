<?php

namespace App\Services\EmbedProviders;

class VimeoProvider extends AbstractProvider
{
    public function name(): string { return 'Vimeo'; }
    public function aspect(): string { return '16/9'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?(vimeo\.com/|player\.vimeo\.com/video/)#i', $url);
    }

    public function resolve(string $url): ?string
    {
        if (!preg_match('#(?:vimeo\.com/|player\.vimeo\.com/video/)(\d+)#', $url, $m)) {
            return null;
        }
        $id = $m[1];
        $src = 'https://player.vimeo.com/video/' . $id;
        return '<iframe src="' . $src . '" width="560" height="315" '
             . 'frameborder="0" allow="autoplay; fullscreen; picture-in-picture" '
             . 'allowfullscreen loading="lazy" title="Vimeo video"></iframe>';
    }
}
