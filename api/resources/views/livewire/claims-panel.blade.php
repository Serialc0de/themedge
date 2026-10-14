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
            <div class="flex items-start justify-between gap-3 mb-2">
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
                        @if ($claim->ai_confidence !== null)
                            <span class="text-xs text-gray-500">
                                AI: {{ $claim->ai_confidence_percent }}%
                            </span>
                        @endif
                    </div>

                    <div class="font-medium text-gray-900 dark:text-gray-100 text-base">
                        "{{ $claim->claim_text }}"
                    </div>

                    @if ($claim->context)
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 italic">
                            Contesto: {{ \Illuminate\Support\Str::limit($claim->context, 200) }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Suggerimento AI --}}
            @if ($claim->hasAiSuggestion())
                <div class="mt-2 mb-3 p-2 rounded bg-white/60 dark:bg-black/30 text-xs text-gray-600 dark:text-gray-400">
                    🤖 <strong>Suggerimento AI:</strong>
                    @if ($claim->ai_suggested_verdict === 'verified') ✅ Verificato
                    @elseif ($claim->ai_suggested_verdict === 'false') ❌ Falso
                    @else ⚠️ In verifica
                    @endif
                    @if ($claim->ai_suggested_source_name)
                        · Fonte: {{ $claim->ai_suggested_source_name }}
                    @endif
                </div>
            @endif

            {{-- Bottoni (solo se pending) --}}
            @if ($claim->isPending())
                <div class="flex flex-wrap gap-2 mt-3">
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
                <div class="mt-2 text-xs text-gray-500">
                    Processato {{ $claim->approved_at?->diffForHumans() }}
                    @if ($claim->factCheck)
                        · Fact-check #{{ $claim->factCheck->id }} pubblicato
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
