<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Entity;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DetectedEntitiesController extends Controller
{
    /**
     * Collega tutte le entità esistenti rilevate all'articolo.
     */
    public function linkExisting(Article $record)
    {
        $existing = $record->detected_existing_entities;

        if (empty($existing)) {
            Notification::make()
                ->title('Nessuna entità esistente da collegare')
                ->warning()
                ->send();

            return redirect()->back();
        }

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
                ->title('Nessuna entità esistente da collegare')
                ->warning()
                ->send();

            return redirect()->back();
        }

        $record->entities()->syncWithoutDetaching($idsToLink);

        // Rimuovi le entità collegate dal detected_entities
        $this->removeLinkedFromDetected($record, array_keys($idsToLink));

        Notification::make()
            ->title('✅ Entità collegate')
            ->body(count($idsToLink) . ' entità collegate all\'articolo.')
            ->success()
            ->send();

        return redirect()->back();
    }

    /**
     * Crea le nuove entità rilevate e le collega all'articolo.
     */
    public function createNew(Article $record)
    {
        $new = $record->detected_new_entities;

        if (empty($new)) {
            Notification::make()
                ->title('Nessuna nuova entità da creare')
                ->warning()
                ->send();

            return redirect()->back();
        }

        $created = [];
        $idsToLink = [];

        foreach ($new as $item) {
            $name = $item['name'];
            $slug = Str::slug($name);

            // Verifica se esiste già (per sicurezza)
            $existing = Entity::where('slug', $slug)->first();
            if ($existing) {
                $idsToLink[$existing->id] = [
                    'mentions' => $item['mentions'] ?? 1,
                    'context' => $item['context'] ?? null,
                    'order' => 0,
                ];
                continue;
            }

            // Crea nuova entità
            $entity = Entity::create([
                'type' => $item['type'],
                'name' => $name,
                'slug' => $slug,
                'bio' => null,
                'aliases' => null,
                'metadata' => ['ai_detected' => true],
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
            $record->entities()->syncWithoutDetaching($idsToLink);
        }

        // Rimuovi le entità create dal detected_entities
        $this->removeLinkedFromDetected($record, array_keys($idsToLink));

        Notification::make()
            ->title('✅ Entità create e collegate')
            ->body(count($created) . ' nuove entità create e collegate.')
            ->success()
            ->send();

        return redirect()->back();
    }

    /**
     * Rimuovi le entità collegate dal campo detected_entities.
     */
    protected function removeLinkedFromDetected(Article $record, array $linkedIds): void
    {
        $detected = $record->detected_entities ?? [];
        $entities = $detected['entities'] ?? [];

        $remaining = array_values(array_filter($entities, function ($e) use ($linkedIds) {
            return !in_array($e['entity_id'] ?? null, $linkedIds);
        }));

        if (empty($remaining)) {
            $record->update(['detected_entities' => null]);
        } else {
            $detected['entities'] = $remaining;
            $record->update(['detected_entities' => $detected]);
        }
    }
}
