<x-filament-panels::page>
    @php
        $categories = $this->getCategoriesWithStats();
        $global = $this->getGlobalStats();
    @endphp

    {{-- STATS GLOBALI --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $global['total_categories'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Categorie totali</p>
            <p class="mt-1 text-xs text-gray-400">{{ $global['active_categories'] }} attive</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-3xl font-bold text-success-600 dark:text-success-400">{{ $global['total_articles'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Articoli totali</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-3xl font-bold text-warning-600 dark:text-warning-400">{{ $global['total_live_blogs'] }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Live blog totali</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-3xl font-bold text-info-600 dark:text-info-400">🎛️</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Hub attivo</p>
        </div>
    </div>

    {{-- GRIGLIA CATEGORIE --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($categories as $cat)
            <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                {{-- Bordo colorato --}}
                <div class="h-1" style="background-color: {{ $cat['color'] }}"></div>

                {{-- Header --}}
                <div class="p-5">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-4xl" aria-hidden="true">{{ $cat['emoji'] }}</span>
                            <div>
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $cat['name'] }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">/{{ $cat['slug'] }}</p>
                            </div>
                        </div>
                        @if (!$cat['is_active'])
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-800">Inattiva</span>
                        @endif
                    </div>

                    @if ($cat['description'])
                        <p class="mb-4 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ $cat['description'] }}</p>
                    @endif

                    {{-- Stats --}}
                    <div class="mb-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                            <p class="font-bold text-gray-900 dark:text-gray-100">{{ $cat['published'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400">📄 Pubblicati</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                            <p class="font-bold text-gray-900 dark:text-gray-100">{{ $cat['drafts'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400">📝 Bozze</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                            <p class="font-bold text-red-600 dark:text-red-400">{{ $cat['live_active'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400">🔴 Live attivi</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-800">
                            <p class="font-bold text-gray-900 dark:text-gray-100">{{ $cat['live_ended'] }}</p>
                            <p class="text-gray-500 dark:text-gray-400">✅ Live chiusi</p>
                        </div>
                    </div>

                    {{-- Ultimo articolo --}}
                    @if ($cat['last_article'])
                        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-2 text-xs dark:border-gray-700 dark:bg-gray-800">
                            <p class="mb-1 font-bold uppercase tracking-wider text-gray-500">Ultimo articolo</p>
                            <p class="line-clamp-1 text-gray-800 dark:text-gray-200">{{ $cat['last_article']['title'] }}</p>
                        </div>
                    @endif

                    {{-- Views --}}
                    <div class="mb-4 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">👁 {{ number_format($cat['views']) }} views</span>
                    </div>

                    {{-- Azioni --}}
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $cat['admin_url'] }}" class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-700">
                            ✏️ Modifica
                        </a>
                        <a href="{{ $cat['frontend_url'] }}" target="_blank" rel="noopener" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            🌐 Vedi hub
                        </a>
                        <a href="{{ $cat['create_article_url'] }}" class="rounded-lg border border-success-300 bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-700 transition hover:bg-success-100 dark:border-success-700 dark:bg-success-950/30 dark:text-success-300">
                            📝 Articolo
                        </a>
                        <a href="{{ $cat['create_live_url'] }}" class="rounded-lg border border-warning-300 bg-warning-50 px-3 py-1.5 text-xs font-semibold text-warning-700 transition hover:bg-warning-100 dark:border-warning-700 dark:bg-warning-950/30 dark:text-warning-300">
                            🔴 Live
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="mb-2 text-4xl">📁</p>
                <h3 class="mb-2 font-serif text-xl font-bold">Nessuna categoria</h3>
                <p class="text-gray-600 dark:text-gray-400">Crea la prima categoria per iniziare.</p>
            </div>
        @endforelse
    </div>

    {{-- CTA --}}
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="/admin/categories/create" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700">
            ➕ Nuova categoria
        </a>
        <a href="/admin/articles/create" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            📝 Nuovo articolo
        </a>
        <a href="/admin/live-blogs/create" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
            🔴 Nuovo live blog
        </a>
    </div>
</x-filament-panels::page>
