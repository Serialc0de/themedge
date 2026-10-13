<?php

namespace App\Livewire;

use App\Models\Article;
use Filament\Notifications\Notification;
use Livewire\Component;

class SeoSuggestionsPanel extends Component
{
    public ?Article $record = null;

    public function mount(Article $record): void
    {
        $this->record = $record;
    }

    public function applySeoTitle(): void
    {
        $title = $this->record->ai_seo_suggestions['seo_title'] ?? null;
        if (!$title) {
            Notification::make()->title('Nessun SEO title disponibile')->warning()->send();
            return;
        }

        $this->record->update(['meta_title' => $title]);
        $this->record->refresh();

        Notification::make()
            ->title('✅ SEO Title applicato')
            ->body($title)
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function applyMetaDescription(): void
    {
        $desc = $this->record->ai_seo_suggestions['meta_description'] ?? null;
        if (!$desc) {
            Notification::make()->title('Nessuna meta description disponibile')->warning()->send();
            return;
        }

        $this->record->update(['meta_description' => $desc]);
        $this->record->refresh();

        Notification::make()
            ->title('✅ Meta description applicata')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function applySlug(): void
    {
        $slug = $this->record->ai_seo_suggestions['slug_suggestion'] ?? null;
        if (!$slug) {
            Notification::make()->title('Nessuno slug disponibile')->warning()->send();
            return;
        }

        $this->record->update(['slug' => $slug]);
        $this->record->refresh();

        Notification::make()
            ->title('✅ Slug applicato')
            ->body('Redirect creato automaticamente.')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function applyAll(): void
    {
        $seo = $this->record->ai_seo_suggestions ?? [];

        if (empty($seo['seo_title'])) {
            Notification::make()->title('Nessun suggerimento disponibile')->warning()->send();
            return;
        }

        $this->record->update([
            'meta_title' => $seo['seo_title'] ?? $this->record->meta_title,
            'meta_description' => $seo['meta_description'] ?? $this->record->meta_description,
            'slug' => $seo['slug_suggestion'] ?? $this->record->slug,
        ]);

        $this->record->refresh();

        Notification::make()
            ->title('✅ Tutti i suggerimenti applicati')
            ->body('SEO Title + Meta Description + Slug aggiornati.')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.seo-suggestions-panel');
    }
}
