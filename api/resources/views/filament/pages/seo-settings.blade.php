<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check" size="lg">
                Salva impostazioni
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
