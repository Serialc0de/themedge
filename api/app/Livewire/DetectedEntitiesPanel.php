<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Entity;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class DetectedEntitiesPanel extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?Article $record = null;

    public function mount(Article $record): void
    {
        $this->record = $record;
    }

    public function linkExistingAction(): Action
    {
        return Action::make('link_existing_detected')
            ->label('✅ Collega esistenti (' . count($this->record->detected_existing_entities) . ')')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Collega entità esistenti')
            ->modalDescription('Le entità già nel database verranno collegate all\'articolo.')
            ->action(function () {
                $existing = $this->record->detected_existing_entities;

                $idsToLink = [];
                foreach ($existing as $item) {
                    if (!empty($item['entity_id'])) {
                        $idsToLink[$item['entity_id']] = [
                            'mentions' => $item['mentions'] ?? 1,
                            'context' => $item['context'] ?? null,
                            'order' => 0,
                        ];
                    }
                }

                if (empty($idsToLink)) {
                    Notification::make()
                        ->title('Nessuna entità da collegare')
                        ->warning()
                        ->send();
                    return;
                }

                $this->record->entities()->syncWithoutDetaching($idsToLink);
                $this->removeLinkedFromDetected(array_keys($idsToLink));

                Notification::make()
                    ->title('✅ Entità collegate')
                    ->body(count($idsToLink) . ' entità collegate.')
                    ->success()
                    ->send();

                $this->dispatch('refresh');
            })
            ->visible(fn () => count($this->record->detected_existing_entities) > 0);
    }

    public function createNewAction(): Action
    {
        return Action::make('create_new_detected')
            ->label('🆕 Crea nuove (' . count($this->record->detected_new_entities) . ')')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Crea nuove entità')
            ->modalDescription('Le nuove entità verranno create nel database e collegate all\'articolo.')
            ->action(function () {
                $new = $this->record->detected_new_entities;

                $created = [];
                $idsToLink = [];

                foreach ($new as $item) {
                    $name = $item['name'];
                    $slug = Str::slug($name);

                    $existing = Entity::where('slug', $slug)->first();
                    if ($existing) {
                        $idsToLink[$existing->id] = [
                            'mentions' => $item['mentions'] ?? 1,
                            'context' => $item['context'] ?? null,
                            'order' => 0,
                        ];
                        continue;
                    }

                    $entity = Entity::create([
                        'type' => $item['type'],
                        'name' => $name,
                        'slug' => $slug,
                        'is_published' => true,
                        'order' => 0,
                    ]);

                    $created[] = $entity;
                    $idsToLink[$entity->id] = [
                        'mentions' => $item['mentions'] ?? 1,
                        'context' => $item['context'] ?? null,
                        'order' => 0,
                    ];
                }

                if (!empty($idsToLink)) {
                    $this->record->entities()->syncWithoutDetaching($idsToLink);
                }

                $this->removeLinkedFromDetected(array_keys($idsToLink));

                Notification::make()
                    ->title('✅ Entità create e collegate')
                    ->body(count($created) . ' nuove entità create.')
                    ->success()
                    ->send();

                $this->dispatch('refresh');
            })
            ->visible(fn () => count($this->record->detected_new_entities) > 0);
    }

    protected function removeLinkedFromDetected(array $linkedIds): void
    {
        $detected = $this->record->detected_entities ?? [];
        $entities = $detected['entities'] ?? [];

        $remaining = array_values(array_filter($entities, function ($e) use ($linkedIds) {
            return !in_array($e['entity_id'] ?? null, $linkedIds);
        }));

        if (empty($remaining)) {
            $this->record->update(['detected_entities' => null]);
        } else {
            $detected['entities'] = $remaining;
            $this->record->update(['detected_entities' => $detected]);
        }

        $this->record->refresh();
    }

    public function render()
    {
        return view('livewire.detected-entities-panel');
    }
}
