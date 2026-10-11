<x-filament-panels::page>
    <x-filament::section heading="Chi appare in redazione" icon="heroicon-o-users">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Per far apparire un utente nella pagina pubblica <code>/redazione</code>, vai su
            <a href="/admin/users" class="text-primary-600 underline font-bold">Utenti</a>,
            modifica l'utente e attiva <strong>"Mostra in redazione"</strong>.
        </p>
    </x-filament::section>

    @php
        $team = \App\Models\User::where('show_in_editorial_board', true)
            ->where('is_active', true)
            ->with('editorialRole')
            ->orderBy('board_order')
            ->orderBy('name')
            ->get();

        $allRoles = \App\Models\EditorialRole::orderBy('sort_order')->get();
    @endphp

    <x-filament::section heading="Team attuale ({{ $team->count() }})" icon="heroicon-o-user-circle">
        @if ($team->isEmpty())
            <div class="text-center py-8">
                <p class="text-4xl mb-3">👥</p>
                <p class="text-gray-500">Nessun utente è ancora visibile nella redazione pubblica.</p>
                <p class="text-sm text-gray-400 mt-2">Vai su Utenti → modifica → attiva "Mostra in redazione"</p>
            </div>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($team as $member)
                    <div class="border-2 border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-3 mb-3">
                            @if ($member->avatar)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->avatar) }}"
                                     alt="{{ $member->name }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-2xl font-serif text-gray-500">
                                    {{ substr($member->name, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                @if ($member->editorialRole)
                                    <p class="text-xs uppercase font-bold text-primary-600">{{ $member->editorialRole->name }}</p>
                                @endif
                                <p class="font-bold">{{ $member->name }}</p>
                                @if ($member->job_title)
                                    <p class="text-xs text-gray-500">{{ $member->job_title }}</p>
                                @endif
                            </div>
                        </div>

                        @if ($member->bio)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ \Illuminate\Support\Str::limit($member->bio, 100) }}</p>
                        @endif

                        <a href="/admin/users/{{ $member->id }}/edit" class="text-xs text-primary-600 hover:underline font-bold">
                            Modifica →
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    @if ($allRoles->isNotEmpty())
        <x-filament::section heading="Ruoli editoriali disponibili" icon="heroicon-o-identification">
            <div class="grid md:grid-cols-3 gap-3">
                @foreach ($allRoles as $role)
                    <div class="p-3 border border-gray-200 dark:border-gray-700 rounded">
                        <p class="font-bold">{{ $role->name }}</p>
                        @if ($role->description)
                            <p class="text-xs text-gray-500">{{ $role->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
