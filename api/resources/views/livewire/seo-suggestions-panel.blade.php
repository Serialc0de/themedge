<div class="space-y-4">
    @php
        $seo = $record->ai_seo_suggestions ?? [];
        $score = $seo['seo_score'] ?? 0;
        $scoreColor = $score >= 80 ? 'green' : ($score >= 60 ? 'amber' : 'red');
    @endphp

    {{-- Score --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <div>
            <div class="text-xs uppercase tracking-widest text-gray-500 mb-1">SEO Score</div>
            <div class="text-3xl font-bold text-{{ $scoreColor }}-600 dark:text-{{ $scoreColor }}-400">
                {{ $score }}/100
            </div>
        </div>
        <div class="text-xs text-gray-500 text-right">
            {{ $record->ai_seo_generated_at?->diffForHumans() }}<br>
            {{ $record->ai_seo_model }}
        </div>
    </div>

    {{-- SEO Title --}}
    <div class="p-3 border rounded-lg border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="text-xs uppercase tracking-widest text-amber-700 dark:text-amber-300 mb-1">
                    📌 SEO Title ({{ mb_strlen($seo['seo_title'] ?? '') }} char)
                </div>
                <div class="font-medium">{{ $seo['seo_title'] ?? '—' }}</div>
                @if ($record->meta_title)
                    <div class="text-xs text-gray-500 mt-1">Attuale: {{ $record->meta_title }}</div>
                @endif
            </div>
            <button
                type="button"
                wire:click="applySeoTitle"
                class="flex-shrink-0 px-4 py-2 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded transition"
            >
                📌 Applica
            </button>
        </div>
    </div>

    {{-- Meta Description --}}
    <div class="p-3 border rounded-lg border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="text-xs uppercase tracking-widest text-amber-700 dark:text-amber-300 mb-1">
                    📝 Meta Description ({{ mb_strlen($seo['meta_description'] ?? '') }} char)
                </div>
                <div>{{ $seo['meta_description'] ?? '—' }}</div>
                @if ($record->meta_description)
                    <div class="text-xs text-gray-500 mt-1">Attuale: {{ \Illuminate\Support\Str::limit($record->meta_description, 80) }}</div>
                @endif
            </div>
            <button
                type="button"
                wire:click="applyMetaDescription"
                class="flex-shrink-0 px-4 py-2 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded transition"
            >
                📝 Applica
            </button>
        </div>
    </div>

    {{-- Slug --}}
    <div class="p-3 border rounded-lg border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="text-xs uppercase tracking-widest text-amber-700 dark:text-amber-300 mb-1">🔗 Slug Suggestion</div>
                <div class="font-mono text-sm">{{ $seo['slug_suggestion'] ?? '—' }}</div>
                <div class="text-xs text-gray-500 mt-1">Attuale: {{ $record->slug }}</div>
            </div>
            <button
                type="button"
                wire:click="applySlug"
                wire:confirm="Cambiare slug può rompere i link esistenti. Verrà creato un redirect automatico. Continuare?"
                class="flex-shrink-0 px-4 py-2 text-sm font-bold bg-amber-600 hover:bg-amber-700 text-white rounded transition"
            >
                🔗 Applica
            </button>
        </div>
    </div>

    {{-- Keywords + Hashtags --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="p-3 border rounded-lg bg-white dark:bg-gray-900">
            <div class="text-xs uppercase tracking-widest text-gray-500 mb-2">🔑 Keyword</div>
            <div class="flex flex-wrap gap-1">
                @foreach ($seo['primary_keywords'] ?? [] as $kw)
                    <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-800 rounded">{{ $kw }}</span>
                @endforeach
            </div>
        </div>
        <div class="p-3 border rounded-lg bg-white dark:bg-gray-900">
            <div class="text-xs uppercase tracking-widest text-gray-500 mb-2">#️⃣ Hashtag</div>
            <div class="flex flex-wrap gap-1">
                @foreach ($seo['hashtags'] ?? [] as $ht)
                    <span class="px-2 py-0.5 text-xs bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 rounded">{{ $ht }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Suggerimenti --}}
    @if (!empty($seo['suggestions']))
        <div class="p-3 border rounded-lg bg-white dark:bg-gray-900">
            <div class="text-xs uppercase tracking-widest text-gray-500 mb-2">💡 Suggerimenti</div>
            <ul class="space-y-1 text-sm">
                @foreach ($seo['suggestions'] as $s)
                    <li class="flex gap-2"><span class="text-gray-400">▸</span><span>{{ $s }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Apply All --}}
    <div class="flex justify-end pt-2">
        <button
            type="button"
            wire:click="applyAll"
            wire:confirm="Applicare tutti i suggerimenti (SEO Title, Meta Description, Slug)?"
            class="px-6 py-3 text-sm font-bold bg-green-600 hover:bg-green-700 text-white rounded transition"
        >
            ⚡ Applica tutti
        </button>
    </div>
</div>
