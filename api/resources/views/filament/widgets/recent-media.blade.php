<x-filament-widgets::widget>
    <x-filament::section
        heading="🖼️ Media recenti"
        :description="$media->isEmpty() ? 'Nessun media caricato' : 'Ultimi 12 file caricati'"
    >
        @if ($media->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <p class="text-sm">Nessun file nella libreria.</p>
                <p class="text-xs mt-1">Vai su <a href="/admin/media" class="text-primary-600 underline">Media</a> per caricarne.</p>
            </div>
        @else
            <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach ($media as $item)
                    @php $url = $item->getFirstMediaUrl('library'); @endphp
                    @if ($url)
                        <a href="{{ $url }}" target="_blank"
                           class="group relative aspect-square overflow-hidden rounded-lg bg-gray-100 hover:ring-2 hover:ring-primary-500 transition"
                           title="{{ $item->title }}">
                            <img src="{{ $url }}"
                                 alt="{{ $item->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition">
                                <p class="text-xs text-white truncate">{{ $item->title }}</p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mt-4 text-center">
                <a href="/admin/media" class="text-sm text-primary-600 hover:text-primary-500 font-semibold">
                    Vedi tutta la libreria →
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
