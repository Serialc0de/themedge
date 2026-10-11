<?php

namespace App\Services\EmbedProviders;

class TwitterProvider extends AbstractProvider
{
    public function name(): string { return 'Twitter / X'; }
    public function aspect(): string { return 'auto'; }

    public function matches(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?(twitter|x)\.com/[^/]+/status/\d+#i', $url);
    }

    public function resolve(string $url): ?string
    {
        if (!preg_match('#(?:twitter|x)\.com/([^/]+)/status/(\d+)#', $url, $m)) {
            return null;
        }
        $user = htmlspecialchars($m[1], ENT_QUOTES);
        $id = $m[2];
        // blockquote conforme a widgets.js di Twitter
        return '<blockquote class="twitter-tweet" data-dnt="true">'
             . '<a href="https://twitter.com/' . $user . '/status/' . $id . '"></a>'
             . '</blockquote>';
    }
}
