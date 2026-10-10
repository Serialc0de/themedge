<div class="space-y-4">
    <div>
        <h3 class="text-sm font-bold uppercase text-gray-500 mb-1">Descrizione</h3>
        <p class="text-base">{{ $activity->description }}</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-bold uppercase text-gray-500 mb-1">Data</h3>
            <p>{{ $activity->created_at->format('d/m/Y H:i:s') }}</p>
        </div>
        <div>
            <h3 class="text-sm font-bold uppercase text-gray-500 mb-1">Utente</h3>
            <p>{{ $activity->causer?->name ?? 'Sistema' }}</p>
        </div>
    </div>

    @if ($activity->properties && $activity->properties->isNotEmpty())
        <div>
            <h3 class="text-sm font-bold uppercase text-gray-500 mb-2">Modifiche</h3>
            <div class="space-y-2">
                @foreach ($activity->properties as $key => $values)
                    @if (is_array($values))
                        <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                            <p class="text-xs font-bold uppercase text-gray-500 mb-2">{{ $key }}</p>
                            @if (isset($values['old']))
                                <p class="text-sm"><span class="text-red-600 font-bold">- Vecchio:</span> <code class="text-xs">{{ is_array($values['old']) ? json_encode($values['old']) : $values['old'] }}</code></p>
                            @endif
                            @if (isset($values['attributes']))
                                <p class="text-sm"><span class="text-green-600 font-bold">+ Nuovo:</span> <code class="text-xs">{{ is_array($values['attributes']) ? json_encode($values['attributes']) : $values['attributes'] }}</code></p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
