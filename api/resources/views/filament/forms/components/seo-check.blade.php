@php
    $title = $get('title') ?? '';
    $metaTitle = $get('meta_title') ?? $title;
    $metaDescription = $get('meta_description') ?? $get('excerpt') ?? '';
    $content = strip_tags($get('content') ?? '');
    $slug = $get('slug') ?? '';
    $featuredImage = $get('featured_image');

    $titleLen = mb_strlen($metaTitle);
    $descLen = mb_strlen($metaDescription);
    $wordCount = str_word_count($content);
    $readingTime = max(1, ceil($wordCount / 200));

    $checks = [
        [
            'label' => 'Meta title',
            'value' => "{$titleLen} caratteri",
            'status' => $titleLen >= 40 && $titleLen <= 60 ? 'good' : ($titleLen > 0 ? 'warning' : 'error'),
            'hint' => 'Consigliato: 40-60 caratteri',
        ],
        [
            'label' => 'Meta description',
            'value' => "{$descLen} caratteri",
            'status' => $descLen >= 120 && $descLen <= 160 ? 'good' : ($descLen > 0 ? 'warning' : 'error'),
            'hint' => 'Consigliato: 120-160 caratteri',
        ],
        [
            'label' => 'Contenuto',
            'value' => "{$wordCount} parole · ~{$readingTime} min",
            'status' => $wordCount >= 300 ? 'good' : ($wordCount > 0 ? 'warning' : 'error'),
            'hint' => 'Minimo consigliato: 300 parole',
        ],
        [
            'label' => 'Slug URL',
            'value' => $slug ?: 'non impostato',
            'status' => $slug && strlen($slug) <= 60 ? 'good' : 'warning',
            'hint' => 'Max 60 caratteri, solo minuscole e trattini',
        ],
        [
            'label' => 'Immagine di copertina',
            'value' => $featuredImage ? 'presente' : 'assente',
            'status' => $featuredImage ? 'good' : 'warning',
            'hint' => 'Consigliata per social e hero',
        ],
    ];
@endphp

<div class="space-y-3">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        @foreach ($checks as $check)
            <div class="flex items-center gap-3 p-3 rounded-lg border
                @if ($check['status'] === 'good') border-green-300 bg-green-50 dark:bg-green-950/20
                @elseif ($check['status'] === 'warning') border-yellow-300 bg-yellow-50 dark:bg-yellow-950/20
                @else border-red-300 bg-red-50 dark:bg-red-950/20
                @endif">
                <span class="text-xl flex-shrink-0">
                    @if ($check['status'] === 'good') ✅
                    @elseif ($check['status'] === 'warning') ⚠️
                    @else ❌
                    @endif
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $check['label'] }}: <span class="font-normal">{{ $check['value'] }}</span>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $check['hint'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Google Preview --}}
    <div class="mt-4 p-4 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-3">🔍 Anteprima Google</p>
        <div class="space-y-1">
            <p class="text-xs text-gray-600 dark:text-gray-400">
                https://themedge.com/articolo/<span class="text-green-700 dark:text-green-400">{{ $slug ?: 'il-tuo-slug' }}</span>
            </p>
            <p class="text-lg text-blue-700 dark:text-blue-400 hover:underline cursor-pointer font-normal leading-tight">
                {{ $metaTitle ?: 'Titolo del tuo articolo' }}
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300 leading-snug">
                {{ $metaDescription ? \Illuminate\Support\Str::limit($metaDescription, 160) : 'La meta description apparirà qui. Aggiungila per migliorare il CTR sui risultati di ricerca.' }}
            </p>
        </div>
    </div>
</div>
