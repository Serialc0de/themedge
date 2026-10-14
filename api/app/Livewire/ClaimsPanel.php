<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\ArticleClaim;
use App\Models\ArticleFactCheck;
use Filament\Notifications\Notification;
use Livewire\Component;

class ClaimsPanel extends Component
{
    public ?Article $record = null;
    
    /** Campi editabili per ogni claim (key = claim_id) */
    public array $editing = [];

    public function mount(Article $record): void
    {
        $this->record = $record;
        $this->loadEditing();
    }

    protected function loadEditing(): void
    {
        foreach ($this->record->claims()->get() as $claim) {
            $this->editing[$claim->id] = [
                'source_name' => $claim->source_name ?? $claim->ai_suggested_source_name ?? '',
                'source_url' => $claim->source_url ?? $claim->ai_suggested_source_url ?? '',
                'note' => $claim->note ?? '',
            ];
        }
    }

    public function approveClaim(int $claimId, string $verdict): void
    {
        if (!in_array($verdict, ['verified', 'in_review', 'false'], true)) {
            Notification::make()->title('Verdetto non valido')->danger()->send();
            return;
        }

        $claim = ArticleClaim::where('id', $claimId)
            ->where('article_id', $this->record->id)
            ->first();

        if (!$claim) {
            Notification::make()->title('Claim non trovato')->danger()->send();
            return;
        }

        // Prendi i dati editati
        $editData = $this->editing[$claimId] ?? [];

        // DEDUPLICAZIONE: cerca un fact-check esistente con claim simile
        $existing = ArticleFactCheck::where('article_id', $this->record->id)
            ->where(function ($q) use ($claim) {
                // Match esatto del claim
                $q->where('claim', $claim->claim_text);
                // O match fuzzy (prime 30 char)
                $q->orWhere('claim', 'like', '%' . mb_substr($claim->claim_text, 0, 30) . '%');
            })
            ->first();

        if ($existing) {
            // Aggiorna il fact-check esistente
            $existing->update([
                'verdict' => $verdict,
                'source_url' => $editData['source_url'] ?: $existing->source_url,
                'source_name' => $editData['source_name'] ?: $existing->source_name,
                'note' => $editData['note'] ?: $existing->note,
            ]);

            $factCheck = $existing;

            Notification::make()
                ->title('✅ Fact-check aggiornato')
                ->body('Esisteva già un fact-check simile. Aggiornato invece di duplicare.')
                ->success()
                ->send();
        } else {
            // Crea il fact-check pubblico con i dati dell'editore
            $factCheck = ArticleFactCheck::create([
                'article_id' => $this->record->id,
                'claim' => $claim->claim_text,
                'verdict' => $verdict,
                'source_url' => $editData['source_url'] ?: null,
                'source_name' => $editData['source_name'] ?: null,
                'note' => $editData['note'] ?: null,
                'order' => $claim->order,
            ]);

            Notification::make()
                ->title('✅ Fact-check approvato')
                ->body('Il claim è ora pubblicato come ' . $claim->status_label)
                ->success()
                ->send();
        }

        // Aggiorna il claim
        $claim->update([
            'status' => $verdict,
            'source_url' => $editData['source_url'] ?: null,
            'source_name' => $editData['source_name'] ?: null,
            'note' => $editData['note'] ?: null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'fact_check_id' => $factCheck->id,
        ]);

        $this->record->refresh();
        $this->dispatch('$refresh');
    }

    public function discardClaim(int $claimId): void
    {
        $claim = ArticleClaim::where('id', $claimId)
            ->where('article_id', $this->record->id)
            ->first();

        if (!$claim) return;

        $claim->update([
            'status' => 'discarded',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Notification::make()
            ->title('🗑️ Claim scartato')
            ->success()
            ->send();

        $this->record->refresh();
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.claims-panel', [
            'claims' => $this->record->claims()->get(),
            'pendingCount' => $this->record->pendingClaims()->count(),
            'approvedCount' => $this->record->claims()->whereIn('status', ['verified', 'in_review', 'false'])->count(),
            'discardedCount' => $this->record->claims()->where('status', 'discarded')->count(),
        ]);
    }
}
