<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Embed Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per la risoluzione e la sanitizzazione degli embed.
    | Ogni provider ha: pattern URL, metodo di risoluzione, allow-list iframe.
    |
    */

    'enabled' => env('EMBEDS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache oEmbed
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('EMBEDS_CACHE_ENABLED', true),
        'ttl' => env('EMBEDS_CACHE_TTL', 86400), // 24h
        'prefix' => 'oembed:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting endpoint /api/oembed
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'max_attempts' => env('EMBEDS_RATE_LIMIT', 60),
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider supportati
    |--------------------------------------------------------------------------
    | name:        identificativo interno
    | class:       resolver class
    | patterns:    regex per riconoscere URL del provider
    | iframe_hosts: domini autorizzati per iframe in sanitizzazione
    | aspect:      aspect-ratio CSS (es. '16/9', '1/1')
    */
    'providers' => [
        'youtube' => [
            'name' => 'YouTube',
            'class' => \App\Services\EmbedProviders\YouTubeProvider::class,
            'patterns' => [
                '#^https?://(www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#',
                '#^https?://(www\.)?youtube\.com/embed/([a-zA-Z0-9_-]{11})#',
                '#^https?://youtu\.be/([a-zA-Z0-9_-]{11})#',
                '#^https?://(www\.)?youtube\.com/shorts/([a-zA-Z0-9_-]{11})#',
            ],
            'iframe_hosts' => ['www.youtube-nocookie.com', 'www.youtube.com', 'youtube.com'],
            'aspect' => '16/9',
        ],

        'vimeo' => [
            'name' => 'Vimeo',
            'class' => \App\Services\EmbedProviders\VimeoProvider::class,
            'patterns' => [
                '#^https?://(www\.)?vimeo\.com/(\d+)#',
                '#^https?://player\.vimeo\.com/video/(\d+)#',
            ],
            'iframe_hosts' => ['player.vimeo.com'],
            'aspect' => '16/9',
        ],

        'spotify' => [
            'name' => 'Spotify',
            'class' => \App\Services\EmbedProviders\SpotifyProvider::class,
            'patterns' => [
                '#^https?://open\.spotify\.com/(track|album|playlist|episode|show|artist)/([a-zA-Z0-9]+)#',
            ],
            'iframe_hosts' => ['open.spotify.com'],
            'aspect' => 'auto',
        ],

        'twitter' => [
            'name' => 'Twitter / X',
            'class' => \App\Services\EmbedProviders\TwitterProvider::class,
            'patterns' => [
                '#^https?://(www\.)?(twitter|x)\.com/[^/]+/status/(\d+)#',
            ],
            'iframe_hosts' => ['platform.twitter.com', 'platform.x.com'],
            'aspect' => 'auto',
        ],

        'instagram' => [
            'name' => 'Instagram',
            'class' => \App\Services\EmbedProviders\InstagramProvider::class,
            'patterns' => [
                '#^https?://(www\.)?instagram\.com/(p|reel|tv)/([a-zA-Z0-9_-]+)#',
            ],
            'iframe_hosts' => ['www.instagram.com', 'instagram.com'],
            'aspect' => 'auto',
        ],

        'tiktok' => [
            'name' => 'TikTok',
            'class' => \App\Services\EmbedProviders\TikTokProvider::class,
            'patterns' => [
                '#^https?://(www\.)?tiktok\.com/@[^/]+/video/(\d+)#',
            ],
            'iframe_hosts' => ['www.tiktok.com', 'tiktok.com'],
            'aspect' => '9/16',
        ],

        'generic' => [
            'name' => 'Generic oEmbed',
            'class' => \App\Services\EmbedProviders\GenericProvider::class,
            'patterns' => [],
            'iframe_hosts' => [],
            'aspect' => '16/9',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanitizzazione HTML
    |--------------------------------------------------------------------------
    | Allow-list dei tag permessi nell'articolo. Se aggiungi un tag qui,
    | verifica anche di averlo aggiunto all'allow-list del frontend (DOMPurify).
    */
    'sanitize' => [
        'allowed_tags' => [
            'p', 'br', 'hr', 'div', 'span',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'strong', 'b', 'em', 'i', 'u', 's', 'small', 'sub', 'sup', 'mark',
            'ul', 'ol', 'li', 'dl', 'dt', 'dd',
            'blockquote', 'cite', 'q',
            'pre', 'code', 'kbd', 'samp',
            'a', 'img', 'figure', 'figcaption', 'picture', 'source',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
            'iframe', 'video', 'audio', 'source', 'track',
        ],
        'allowed_attributes' => [
            '*' => ['class', 'id', 'title', 'style'],
            'a' => ['href', 'target', 'rel', 'title'],
            'img' => ['src', 'alt', 'width', 'height', 'loading', 'srcset', 'sizes'],
            'iframe' => ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'loading', 'title', 'referrerpolicy'],
            'video' => ['src', 'controls', 'width', 'height', 'poster', 'preload', 'muted', 'loop', 'playsinline'],
            'audio' => ['src', 'controls', 'preload'],
            'source' => ['src', 'type', 'srcset', 'sizes'],
            'blockquote' => ['class', 'data-*', 'cite'],
            'div' => ['class', 'data-*'],
            'span' => ['class'],
        ],
        'blocked_iframe_hosts' => [
            'localhost', '127.0.0.1', '0.0.0.0', '::1',
            '169.254.', '10.', '192.168.', '172.16.', '172.17.', '172.18.',
            '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.',
            '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.',
            '172.31.',
        ],
    ],
];
