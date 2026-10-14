<div class="space-y-4">
    {{-- Header con statistiche --}}
    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            <strong>{{ $claims->count() }}</strong> claim
            · <span class="text-gray-600 dark:text-gray-400">{{ $pendingCount }} da verificare</span>
            · <span class="text-green-600 dark:text-green-400">{{ $approvedCount }} approvati</span>
            · <span class="text-gray-400">{{ $discardedCount }} scartati</span>
        </div>
        <div class="text-xs text-gray-500">
            🤖 Generato da AI · Verifica manuale richiesta
        </div>
    </div>

    {{-- Avviso etico --}}
    <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-xs text-blue-800 dark:text-blue-200">
        <strong>⚠️ Regole etiche:</strong> L'AI ha <em>proposto</em> questi claim. Prima di approvare, <strong>verifica ogni affermazione</strong> con una fonte attendibile. Inserisci nome + URL fonte. La responsabilità editoriale è tua.
    </div>

    {{-- Lista claim --}}
    @foreach ($claims as $claim)
        <div class="rounded-lg border-2
            @if ($claim->status === 'verified') border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-950/20
            @elseif ($claim->status === 'in_review') border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/20
            @elseif ($claim->status === 'false') border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/20
            @elseif ($claim->status === 'discarded') border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/40 opacity-60
            @else border-blue-300 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/20
            @endif
            p-4"
            wire:key="claim-{{ $claim->id }}"
        >
            {{-- Header del claim --}}
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs uppercase tracking-widest font-bold
                            @if ($claim->status === 'verified') text-green-700 dark:text-green-300
                            @elseif ($claim->status === 'in_review') text-amber-700 dark:text-amber-300
                            @elseif ($claim->status === 'false') text-red-700 dark:text-red-300
                            @else text-blue-700 dark:text-blue-300
                            @endif
                        ">
                            {{ $claim->status_label }}
                        </span>
                    </div>

                    <div class="font-medium text-gray-900 dark:text-gray-100 text-base">
                        "{{ $claim->claim_text }}"
                    </div>
                </div>
            </div>

            {{-- Suggerimento AI (informativo) --}}
            @if ($claim->hasAiSuggestion())
                <div class="mb-3 p-2 rounded bg-white/60 dark:bg-black/30 text-xs text-gray-600 dark:text-gray-400">
                    🤖 <strong>Suggerimento AI:</strong>
                    @if ($claim->ai_suggested_verdict === 'verified') ✅ Verificato
                    @elseif ($claim->ai_suggested_verdict === 'false') ❌ Falso
                    @else ⚠️ In verifica
                    @endif
                    @if ($claim->ai_suggested_source_name)
                        · Fonte suggerita: {{ $claim->ai_suggested_source_name }}
                    @endif
                </div>
            @endif

            {{-- Campi editabili (solo se pending) --}}
            @if ($claim->isPending())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    {{-- Nome fonte --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            📰 Nome fonte <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="editing.{{ $claim->id }}.source_name"
                            placeholder="Es. Sala Stampa Vaticana, ANSA, Ministero..."
                            class="w-full px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                        />
                    </div>

                    {{-- URL fonte --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            🔗 URL fonte (opzionale)
                        </label>
                        <input
                            type="url"
                            wire:model="editing.{{ $claim->id }}.source_url"
                            placeholder="https://..."
                            class="w-full px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                        />
                    </div>

                    {{-- Nota editoriale --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                            📝 Nota editoriale (opzionale)
                        </label>
                        <textarea
                            wire:model="editing.{{ $claim->id }}.note"
                            rows="2"
                            placeholder="Es. Confermato dal comunicato ufficiale"
                            class="w-full px-3 py-2 text-sm rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                        ></textarea>
                    </div>
                </div>

                {{-- Bottoni --}}
                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        wire:click="approveClaim({{ $claim->id }}, 'verified')"
                        wire:confirm="Pubblicare questo claim come ✅ VERIFICATO?"
                        class="px-3 py-1.5 text-xs font-bold bg-green-600 hover:bg-green-700 text-white rounded transition"
                    >
                        ✅ Verificato
                    </button>

                    <button
                        type="button"
                        wire:click="approveClaim({{ $claim->id }}, 'in_review')"
                        wire:confirm="Pubblicare questo claim come ⚠️ IN VERIFICA?"
                        class="px-3 py-1.5 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded transition"
                    >
                        ⚠️ In verifica
                    </button>

                    <button
                        type="button"
                        wire:click="approveClaim({{ $claim->id }}, 'false')"
                        wire:confirm="Pubblicare questo claim come ❌ FALSO?"
                        class="px-3 py-1.5 text-xs font-bold bg-red-600 hover:bg-red-700 text-white rounded transition"
                    >
                        ❌ Falso
                    </button>

                    <button
                        type="button"
                        wire:click="discardClaim({{ $claim->id }})"
                        wire:confirm="Scartare questo claim? Non verrà pubblicato."
                        class="px-3 py-1.5 text-xs font-bold bg-gray-500 hover:bg-gray-600 text-white rounded transition"
                    >
                        🗑️ Scarta
                    </button>
                </div>
            @else
                {{-- Claim già processato --}}
                <div class="mt-2 p-2 rounded bg-white/40 dark:bg-black/20 text-xs">
                    @if ($claim->source_name)
                        📰 <strong>Fonte:</strong>
                        @if ($claim->source_url)
                            <a href="{{ $claim->source_url }}" target="_blank" class="underline">{{ $claim->source_name }}</a>
                        @else
                            {{ $claim->source_name }}
                        @endif
                    @endif
                    @if ($claim->note)
                        <br>📝 <em>{{ $claim->note }}</em>
                    @endif
                    <br>
                    <span class="text-gray-500">
                        Processato {{ $claim->approved_at?->diffForHumans() }}
                        @if ($claim->approvedBy)
                            da {{ $claim->approvedBy->name }}
                        @endif
                    </span>
                </div>
            @endif
        </div>
    @endforeach
</div>
