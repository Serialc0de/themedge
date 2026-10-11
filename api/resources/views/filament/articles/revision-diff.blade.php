@php
    $props = $activity->properties->toArray();
    $attributes = $props['attributes'] ?? [];
    $old = $props['old'] ?? [];

    $fieldLabels = [
        'title' => 'Titolo',
        'slug' => 'Slug',
        'excerpt' => 'Sommario',
        'content' => 'Contenuto',
        'status' => 'Stato',
        'published_at' => 'Data pubblicazione',
        'category_id' => 'Categoria',
        'author_id' => 'Autore',
        'is_featured' => 'In evidenza',
    ];

    $statusLabels = [
        'draft' => '📝 Bozza',
        'review' => '⏳ In review',
        'approved' => '✅ Approvato',
        'scheduled' => '🕒 Programmato',
        'published' => '🚀 Pubblicato',
        'archived' => '📦 Archiviato',
    ];
@endphp

<div class="space-y-4">
    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
        <p class="text-sm">
            <strong>{{ $activity->description }}</strong>
        </p>
        <p class="text-xs text-gray-500 mt-1">
            {{ $activity->created_at->format('d/m/Y H:i:s') }} · 
            da <strong>{{ $activity->causer?->name ?? 'Sistema' }}</strong>
        </p>
    </div>

    @if (empty($attributes))
        <p class="text-sm text-gray-500 text-center py-4">
            Nessuna modifica registrata.
        </p>
    @else
        <div class="space-y-3">
            @foreach ($attributes as $field => $newValue)
                @php
                    $oldValue = $old[$field] ?? null;
                    $label = $fieldLabels[$field] ?? $field;

                    // Format valori
                    $format = function ($value) use ($field, $statusLabels) {
                        if ($value === null || $value === '') return '—';
                        
                        if ($field === 'status') {
                            return $statusLabels[$value] ?? $value;
                        }
                        if (is_bool($value)) {
                            return $value ? 'Sì' : 'No';
                        }
                        if (is_array($value)) {
                            return json_encode($value);
                        }
                        if (in_array($field, ['content', 'excerpt'])) {
                            return \Illuminate\Support\Str::limit(strip_tags($value), 200);
                        }
                        return (string) $value;
                    };

                    $oldFormatted = $format($oldValue);
                    $newFormatted = $format($newValue);
                @endphp

                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-3 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            {{ $label }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-gray-200 dark:divide-gray-700">
                        <div class="p-3 bg-red-50/50 dark:bg-red-950/20">
                            <p class="text-xs font-bold text-red-700 dark:text-red-400 mb-1">− Vecchio</p>
                            <p class="text-sm text-gray-800 dark:text-gray-200 break-all">
                                {{ $oldFormatted }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-50/50 dark:bg-green-950/20">
                            <p class="text-xs font-bold text-green-700 dark:text-green-400 mb-1">+ Nuovo</p>
                            <p class="text-sm text-gray-800 dark:text-gray-200 break-all">
                                {{ $newFormatted }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
