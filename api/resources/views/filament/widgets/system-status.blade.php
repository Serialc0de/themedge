<x-filament-widgets::widget>
    <x-filament::section heading="🖥️ Stato sistema">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach ($checks as $check)
                <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0">
                        @if ($check['status'])
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300">
                                ✅
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300">
                                ❌
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ $check['name'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $check['status'] ? 'Operativo' : 'Non disponibile' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
