<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sistema Highlight
    |--------------------------------------------------------------------------
    | Configurazione del sistema di highlight testo (NYT-level).
    */

    // Kill switch globale
    'enabled' => env('HIGHLIGHTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    | - default_public: se true, gli highlight sono pubblici di default
    | - allow_public: se false, gli utenti NON possono rendere pubblici
    */
    'privacy' => [
        'default_public' => env('HIGHLIGHTS_DEFAULT_PUBLIC', false),
        'allow_public' => env('HIGHLIGHTS_ALLOW_PUBLIC', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limiti
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_fragment_length' => 2000,
        'max_note_length' => 2000,
        'min_fragment_length' => 3,
        'max_per_reader' => 500,
        'max_per_article' => 500,
        // Rate limit creazione highlight (per reader)
        'create_rate' => [
            'attempts' => 30,
            'decay_minutes' => 1440, // 30/giorno
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Colori supportati
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'yellow' => [
            'label' => 'Importante',
            'hex' => '#fde047',
        ],
        'green' => [
            'label' => 'OK',
            'hex' => '#86efac',
        ],
        'pink' => [
            'label' => 'Interessante',
            'hex' => '#f9a8d4',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emoji reazioni
    |--------------------------------------------------------------------------
    */
    'emojis' => ['👍', '❤️', '🤔', '🔥', '👏'],

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    */
    'features' => [
        'reactions' => true,
        'notes' => true,
        'public_highlights' => true,
        'heatmap' => true,
        'share_as_image' => false, // da attivare in 3b
    ],
];
