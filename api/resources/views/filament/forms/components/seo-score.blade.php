@php
    $score = $score ?? 0;
    $grade = $grade ?? 'unknown';
    $checks = $checks ?? [];

    $gradeLabel = match ($grade) {
        'excellent' => 'Eccellente',
        'good' => 'Buono',
        'fair' => 'Discreto',
        'poor' => 'Da migliorare',
        default => 'Non calcolato',
    };

    $gradeColor = match ($grade) {
        'excellent' => 'text-green-600 dark:text-green-400',
        'good' => 'text-blue-600 dark:text-blue-400',
        'fair' => 'text-yellow-600 dark:text-yellow-400',
        'poor' => 'text-red-600 dark:text-red-400',
        default => 'text-gray-500',
    };

    $progressColor = match ($grade) {
        'excellent' => 'bg-green-500',
        'good' => 'bg-blue-500',
        'fair' => 'bg-yellow-500',
        'poor' => 'bg-red-500',
        default => 'bg-gray-300',
    };
@endphp

<div class="space-y-4">
    <div class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
        <div class="flex-shrink-0">
            <div class="text-3xl font-black {{ $gradeColor }}">{{ $score }}</div>
            <div class="text-xs uppercase font-bold text-gray-500">/ 100</div>
        </div>
        <div class="flex-1">
            <p class="text-xs uppercase font-bold text-gray-500 mb-1">Punteggio SEO</p>
            <p class="text-lg font-bold {{ $gradeColor }}">{{ $gradeLabel }}</p>
            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-2">
                <div class="h-full {{ $progressColor }} transition-all" style="width: {{ $score }}%"></div>
            </div>
        </div>
    </div>

    @if (! empty($checks))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach ($checks as $check)
                <div class="flex items-center gap-3 p-3 rounded-lg border
                    @if (($check['status'] ?? '') === 'good') border-green-300 bg-green-50 dark:bg-green-950/20
                    @elseif (($check['status'] ?? '') === 'warning') border-yellow-300 bg-yellow-50 dark:bg-yellow-950/20
                    @else border-red-300 bg-red-50 dark:bg-red-950/20
                    @endif">
                    <span class="text-xl flex-shrink-0">
                        @if (($check['status'] ?? '') === 'good') ✅
                        @elseif (($check['status'] ?? '') === 'warning') ⚠️
                        @else ❌
                        @endif
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $check['label'] }}
                            </p>
                            <span class="text-xs font-bold text-gray-500 flex-shrink-0">
                                {{ $check['score'] }}/{{ $check['max'] }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $check['value'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">{{ $check['hint'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
