<x-filament-panels::page>
    @php
        $kpis = $this->getKpis();
        $topArticles = $this->getTopArticles(10);
        $topAuthors = $this->getTopAuthors(10);
        $topCategories = $this->getTopCategories(10);
        $chart = $this->getChartData();
        $trendingTopics = $this->getTrendingTopics(10);
    @endphp

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Articoli pubblicati</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $kpis['total_articles'] }}</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $kpis['published_7d'] }} ultimi 7gg · {{ $kpis['published_30d'] }} ultimi 30gg
            </p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Views totali</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($kpis['total_views']) }}</p>
            <p class="text-xs text-gray-500 mt-1">
                Media {{ number_format($kpis['avg_views']) }} per articolo
            </p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Commenti approvati</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $kpis['total_comments'] }}</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $kpis['comments_7d'] }} ultimi 7gg
            </p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Commenti pending</p>
            <p class="text-3xl font-bold {{ $kpis['pending_comments'] > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">
                {{ $kpis['pending_comments'] }}
            </p>
            @if ($kpis['pending_comments'] > 0)
                <a href="/admin/comments" class="text-xs text-amber-600 hover:underline mt-1 block">Modera →</a>
            @else
                <p class="text-xs text-gray-500 mt-1">Tutto moderato ✅</p>
            @endif
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Autori attivi</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $kpis['total_authors'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Con almeno 1 articolo</p>
        </div>
    </div>

    {{-- Grafico andamento 30 giorni --}}
    <x-filament::section heading="📈 Andamento ultimi 30 giorni" icon="heroicon-o-chart-bar">
        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
            <div class="text-center">
                <p class="text-xs uppercase text-gray-500">Views</p>
                <p class="text-xl font-bold text-primary-600">
                    {{ number_format(array_sum($chart['views'])) }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-xs uppercase text-gray-500">Commenti</p>
                <p class="text-xl font-bold text-blue-600">
                    {{ number_format(array_sum($chart['comments'])) }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-xs uppercase text-gray-500">Pubblicati</p>
                <p class="text-xl font-bold text-green-600">
                    {{ number_format(array_sum($chart['published'])) }}
                </p>
            </div>
        </div>

        @php
            $maxViews = max(array_merge($chart['views'], [1]));
            $points = [];
            $width = 100;
            $height = 30;
            foreach ($chart['views'] as $i => $v) {
                $x = ($i / (count($chart['views']) - 1)) * $width;
                $y = $height - ($v / $maxViews) * $height;
                $points[] = "{$x},{$y}";
            }
        @endphp

        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="w-full h-24" preserveAspectRatio="none">
            <polyline
                points="{{ implode(' ', $points) }}"
                fill="none"
                stroke="#f59e0b"
                stroke-width="0.8"
                vector-effect="non-scaling-stroke"
            />
        </svg>

        <div class="flex justify-between text-xs text-gray-500 mt-2">
            <span>{{ $chart['labels'][0] }}</span>
            <span>{{ $chart['labels'][count($chart['labels']) - 1] }}</span>
        </div>
    </x-filament::section>

    {{-- Griglia report --}}
    <div class="grid md:grid-cols-2 gap-6">

        {{-- Top 10 Articoli --}}
        <x-filament::section heading="📰 Top 10 Articoli per Views">
            <div class="space-y-2">
                @foreach ($topArticles as $i => $a)
                    <div class="flex items-center gap-3 p-2 rounded">
                        <span class="font-mono text-lg font-bold text-gray-400 w-8 flex-shrink-0">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <a href="/admin/articles/{{ $a['id'] }}/edit" class="flex-1 min-w-0 group">
                            <p class="font-bold text-sm truncate group-hover:underline">{{ $a['title'] }}</p>
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($a['published_at'])->format('d/m/Y') }}
                            </p>
                        </a>
                        <span class="flex-shrink-0 bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs font-bold px-2 py-1 rounded">
                            {{ number_format($a['views']) }}
                        </span>
                    </div>
                @endforeach

                @if (empty($topArticles))
                    <p class="text-center text-gray-500 py-4">Nessun articolo pubblicato</p>
                @endif
            </div>
        </x-filament::section>

        {{-- Top 10 Autori --}}
        <x-filament::section heading="✍️ Top 10 Autori per Views">
            <div class="space-y-2">
                @foreach ($topAuthors as $i => $author)
                    <div class="flex items-center gap-3 p-2 rounded">
                        <span class="font-mono text-lg font-bold text-gray-400 w-8 flex-shrink-0">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </span>

                        @if ($author['avatar'])
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($author['avatar']) }}"
                                 class="w-8 h-8 rounded-full object-cover flex-shrink-0" alt="">
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                {{ substr($author['name'], 0, 1) }}
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm truncate">{{ $author['name'] }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $author['articles_count'] }} articoli
                            </p>
                        </div>

                        <span class="flex-shrink-0 bg-success-100 dark:bg-success-900 text-success-800 dark:text-success-200 text-xs font-bold px-2 py-1 rounded">
                            {{ number_format($author['articles_sum_views'] ?? 0) }}
                        </span>
                    </div>
                @endforeach

                @if (empty($topAuthors))
                    <p class="text-center text-gray-500 py-4">Nessun autore con articoli</p>
                @endif
            </div>
        </x-filament::section>

        {{-- Top Categorie --}}
        <x-filament::section heading="📁 Performance Categorie">
            <div class="space-y-2">
                @foreach ($topCategories as $cat)
                    <div class="flex items-center gap-3 p-2 rounded">
                        <div class="w-2 h-8 rounded" style="background: {{ $cat->color ?? '#666' }}"></div>

                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm">{{ $cat->name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $cat->articles_count }} articoli · media {{ number_format($cat->avg_views) }} views
                            </p>
                        </div>

                        <span class="flex-shrink-0 text-xs font-bold">
                            {{ number_format($cat->total_views) }}
                        </span>
                    </div>
                @endforeach

                @if (empty($topCategories))
                    <p class="text-center text-gray-500 py-4">Nessuna categoria con articoli</p>
                @endif
            </div>
        </x-filament::section>

        {{-- Trending Topics --}}
        <x-filament::section heading="🔥 Topic in tendenza (30gg)">
            @if (empty($trendingTopics))
                <p class="text-center text-gray-500 py-4">
                    Nessun topic. Aggiungi tag agli articoli per vedere le tendenze.
                </p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($trendingTopics as $i => $tag)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full border
                            @if ($i < 3) border-red-500 bg-red-50 dark:bg-red-950/20 text-red-700 dark:text-red-400
                            @else border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300
                            @endif">
                            <span class="font-bold text-xs">#{{ $tag->name }}</span>
                            <span class="text-[10px] opacity-70">
                                {{ $tag->articles_count }} art · {{ number_format($tag->total_views) }}
                            </span>
                        </span>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Azioni --}}
    <div class="flex justify-end gap-3">
        <a href="/admin/analytics/export?type=articles"
           class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-xs uppercase tracking-widest font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            📥 Esporta articoli
        </a>
        <a href="/admin/analytics/export?type=authors"
           class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-xs uppercase tracking-widest font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            📥 Esporta autori
        </a>
    </div>
</x-filament-panels::page>
