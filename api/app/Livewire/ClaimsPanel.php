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

    public function mount(Article $record): void
    {
        $this->record = $record;
    }

    /**
     * Approva un claim con un verdetto specifico.
     */
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

        // Crea il fact-check pubblico
        $factCheck = ArticleFactCheck::create([
            'article_id' => $this->record->id,
            'claim' => $claim->claim_text,
            'verdict' => $verdict,
            'source_url' => $claim->source_url ?? $claim->ai_suggested_source_url,
            'source_name' => $claim->source_name ?? $claim->ai_suggested_source_name,
            'note' => $claim->note,
            'order' => $claim->order,
        ]);

        // Aggiorna il claim
        $claim->update([
            'status' => $verdict,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'fact_check_id' => $factCheck->id,
        ]);

        Notification::make()
            ->title('✅ Fact-check approvato')
            ->body('Il claim è ora pubblicato come ' . $claim->status_label)
            ->success()
            ->send();

        $this->record->refresh();
        $this->dispatch('$refresh');
    }

    /**
     * Scarta un claim (non verrà pubblicato).
     */
    public function discardClaim(int $claimId): void
    {
        $claim = ArticleClaim::where('id', $claimId)
            ->where('article_id', $this->record->id)
            ->first();

        if (!$claim) {
            Notification::make()->title('Claim non trovato')->danger()->send();
            return;
        }

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

    /**
     * Salva le modifiche manuali ai campi fonte/nota di un claim.
     */
    public function saveClaim(int $claimId, array $data): void
    {
        $claim = ArticleClaim::where('id', $claimId)
            ->where('article_id', $this->record->id)
            ->first();

        if (!$claim) return;

        $claim->update([
            'source_url' => $data['source_url'] ?? $claim->source_url,
            'source_name' => $data['source_name'] ?? $claim->source_name,
            'note' => $data['note'] ?? $claim->note,
        ]);

        Notification::make()
            ->title('💾 Claim aggiornato')
            ->success()
            ->send();

        $this->record->refresh();
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
