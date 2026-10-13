@php
    $entities = $record?->detected_entities_list ?? [];
    $newEntities = $record?->detected_new_entities ?? [];
    $existingEntities = $record?->detected_existing_entities ?? [];
    $detectedAt = $record?->detected_entities['detected_at'] ?? null;
    $model = $record?->detected_entities['model'] ?? 'N/A';
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            <strong>{{ count($entities) }}</strong> entità trovate
            · <span class="text-green-600 dark:text-green-400">{{ count($existingEntities) }} esistenti</span>
            · <span class="text-blue-600 dark:text-blue-400">{{ count($newEntities) }} nuove</span>
        </div>
        <div class="text-xs text-gray-500">
            {{ $detectedAt ? \Carbon\Carbon::parse($detectedAt)->diffForHumans() : '' }}
            · {{ $model }}
        </div>
    </div>

    <div class="space-y-2">
        @foreach ($entities as $entity)
            <div class="flex items-center justify-between p-3 border rounded-lg
                {{ $entity['exists'] ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/30' }}">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ $entity['entity_icon'] }}</span>
                    <div>
                        <div class="font-medium">{{ $entity['name'] }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $entity['type'] }} · {{ $entity['mentions'] }} menzioni
                            @if ($entity['exists'])
                                · <span class="text-green-600 dark:text-green-400">✅ Già nel DB</span>
                            @else
                                · <span class="text-blue-600 dark:text-blue-400">🆕 Nuova</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 max-w-md truncate">
                    {{ \Illuminate\Support\Str::limit($entity['context'] ?? '', 80) }}
                </div>
            </div>
        @endforeach
    </div>

    @if (count($newEntities) > 0 || count($existingEntities) > 0)
        <div class="flex gap-2 pt-2">
            @if (count($existingEntities) > 0)
                <button
                    type="button"
                    wire:click="linkExisting"
                    wire:confirm="Collegare {{ count($existingEntities) }} entità esistenti all'articolo?"
                    class="px-4 py-2 text-sm font-bold bg-green-600 hover:bg-green-700 text-white rounded transition"
                >
                    ✅ Collega esistenti ({{ count($existingEntities) }})
                </button>
            @endif

            @if (count($newEntities) > 0)
                <button
                    type="button"
                    wire:click="createNew"
                    wire:confirm="Creare {{ count($newEntities) }} nuove entità e collegarle?"
                    class="px-4 py-2 text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white rounded transition"
                >
                    🆕 Crea nuove ({{ count($newEntities) }})
                </button>
            @endif
        </div>
    @endif
</div>
