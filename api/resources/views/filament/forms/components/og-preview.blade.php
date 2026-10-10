@php
    $title = $get('meta_title') ?: $get('title') ?: 'Titolo articolo';
    $description = $get('meta_description') ?: $get('excerpt') ?: 'Descrizione articolo';
    $slug = $get('slug') ?: 'il-tuo-slug';
    $url = 'https://themedge.com/articolo/' . $slug;
    $image = $get('featured_image');

    // Debug: log cosa arriva
    \Log::info('OG Preview image raw:', ['image' => $image, 'type' => gettype($image)]);

    $imageUrl = null;
    if ($image) {
        $first = null;

        if (is_string($image)) {
            $first = $image;
        } elseif (is_array($image)) {
            // Prendi il primo valore
            $first = array_values($image)[0] ?? null;
        }

        if ($first && is_string($first)) {
            // Se è già URL assoluto
            if (str_starts_with($first, 'http://') || str_starts_with($first, 'https://')) {
                $imageUrl = $first;
            } else {
                // Altrimenti costruisci URL con disk public
                $base = rtrim(config('app.public_asset_url', 'https://admin.themedge.com'), '/');
                $imageUrl = $base . '/storage/' . ltrim($first, '/');
            }
        }
    }
@endphp

<div class="space-y-6">
    <p class="text-xs text-gray-500 dark:text-gray-400">
        Anteprima di come apparirà il link quando condiviso sui social.
    </p>

    @if (! $imageUrl)
        <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-300 dark:border-yellow-700 rounded-lg p-3">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                ⚠️ <strong>Nessuna immagine di copertina</strong> caricata. Carica un'immagine per vedere l'anteprima social.
            </p>
        </div>
    @endif

    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">📘 Facebook / LinkedIn</p>
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900 max-w-xl">
            @if ($imageUrl)
                <div class="aspect-[1.91/1] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <img src="{{ $imageUrl }}" alt="{{ $title }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=&quot;flex items-center justify-center h-full text-gray-400 text-sm&quot;>📷 Errore caricamento: ' + this.src + '</div>';">
                </div>
            @else
                <div class="aspect-[1.91/1] bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 text-sm">
                    📷 Nessuna immagine
                </div>
            @endif
            <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs uppercase text-gray-500 dark:text-gray-400 truncate">
                    {{ parse_url($url, PHP_URL_HOST) }}
                </p>
                <p class="font-bold text-base text-gray-900 dark:text-gray-100 leading-tight mt-1 line-clamp-2">
                    {{ $title }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ \Illuminate\Support\Str::limit($description, 120) }}
                </p>
            </div>
        </div>
    </div>

    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">🐦 Twitter / X</p>
        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden bg-white dark:bg-gray-900 max-w-xl">
            @if ($imageUrl)
                <div class="aspect-[1.91/1] bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <img src="{{ $imageUrl }}" alt="{{ $title }}" class="w-full h-full object-cover">
                </div>
            @else
                <div class="aspect-[1.91/1] bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 text-sm">
                    📷 Nessuna immagine
                </div>
            @endif
            <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                <p class="font-bold text-base text-gray-900 dark:text-gray-100 leading-tight line-clamp-1">
                    {{ $title }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ \Illuminate\Support\Str::limit($description, 120) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2 truncate">
                    {{ parse_url($url, PHP_URL_HOST) }}
                </p>
            </div>
        </div>
    </div>

    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">💬 WhatsApp</p>
        <div class="bg-[#e5ddd5] dark:bg-gray-800 rounded-lg p-4 max-w-xl">
            <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden shadow-sm max-w-xs">
                <div class="p-2 bg-gray-100 dark:bg-gray-800">
                    <div class="aspect-[1.91/1] rounded overflow-hidden bg-gray-200 dark:bg-gray-700">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $title }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                </div>
                <div class="p-2">
                    <p class="font-semibold text-sm text-gray-900 dark:text-gray-100 leading-tight line-clamp-2">
                        {{ $title }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                        {{ \Illuminate\Support\Str::limit($description, 80) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 truncate">
                        {{ parse_url($url, PHP_URL_HOST) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if ($imageUrl)
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            <strong>URL immagine:</strong>
            <code class="break-all">{{ $imageUrl }}</code>
        </div>
    @endif
</div>
