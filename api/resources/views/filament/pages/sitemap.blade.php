<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $xml = $this->getSampleXml();
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Articoli</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['articles'] }}</p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Categorie</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['categories'] }}</p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Tag</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['tags'] }}</p>
        </div>

        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">URL totali</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</p>
        </div>
    </div>

    <x-filament::section heading="Informazioni sitemap" icon="heroicon-o-information-circle">
        <div class="space-y-3">
            <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                <p class="text-xs uppercase font-bold text-gray-500">URL sitemap</p>
                <a href="{{ $this->getSitemapUrl() }}" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline break-all">
                    {{ $this->getSitemapUrl() }}
                </a>
            </div>

            <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                <p class="text-xs uppercase font-bold text-gray-500">Robots.txt</p>
                <a href="{{ $this->getRobotsUrl() }}" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline break-all">
                    {{ $this->getRobotsUrl() }}
                </a>
            </div>

            <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                <p class="text-xs uppercase font-bold text-gray-500">RSS Feed</p>
                <a href="{{ $this->getRssUrl() }}" target="_blank" class="text-sm text-primary-600 dark:text-primary-400 hover:underline break-all">
                    {{ $this->getRssUrl() }}
                </a>
            </div>

            <div class="p-3 rounded bg-gray-50 dark:bg-gray-800">
                <p class="text-xs uppercase font-bold text-gray-500">Ultima rigenerazione</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $stats['last_generated'] }}</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Anteprima sitemap.xml" icon="heroicon-o-code-bracket" collapsible collapsed>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Anteprima parziale (prime 20 URL). La sitemap completa è generata dinamicamente dal frontend.
        </p>
        <pre class="p-3 bg-gray-900 dark:bg-black text-green-400 rounded text-xs overflow-x-auto max-h-96"><code>{{ $xml }}</code></pre>
    </x-filament::section>
</x-filament-panels::page>
