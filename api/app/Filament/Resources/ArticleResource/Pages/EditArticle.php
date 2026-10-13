<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\ArticleResource\Widgets\ArticleRevisionsWidget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Anteprima frontend')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => config('app.public_site_url', 'https://themedge.com') . '/articolo/' . $this->record->slug)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'published'),

            Actions\DeleteAction::make()->label('Elimina'),

            Actions\Action::make('generate_ai_summary')
                ->label('✨ Genera TL;DR')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Genera TL;DR')
                ->modalDescription('Verrà generato un riassunto in background con AI (llama3.1:8b). Potrebbe richiedere 2-4 minuti. Riceverai una notifica quando è pronto.')
                ->modalSubmitActionLabel('Genera')
                ->action(function () {
                    \App\Jobs\GenerateArticleSummary::dispatch(
                        $this->record,
                        auth()->id()
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('✨ Generazione avviata')
                        ->body('Il TL;DR verrà generato in background. Riceverai una notifica quando è pronto.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('delete_ai_summary')
                ->label('🗑️ Elimina TL;DR')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Eliminare il TL;DR?')
                ->modalDescription('Il riassunto generato verrà rimosso. Potrai rigenerarlo in qualsiasi momento.')
                ->action(function () {
                    $this->record->update([
                        'ai_summary' => null,
                        'ai_summary_generated_at' => null,
                        'ai_summary_model' => null,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('🗑️ TL;DR eliminato')
                        ->success()
                        ->send();

                    $this->refreshFormData(['ai_summary']);
                })
                ->visible(fn () => $this->record->hasAiSummary()),

            Actions\Action::make('detect_entities')
                ->label('🔍 Rileva entità (AI)')
                ->icon('heroicon-o-tag')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Rileva entità con AI')
                ->modalDescription('L\'AI analizzerà l\'articolo e rileverà le entità (persone, luoghi, organizzazioni, eventi). Potrebbe richiedere 3-4 minuti.')
                ->modalSubmitActionLabel('Avvia')
                ->action(function () {
                    \App\Jobs\DetectArticleEntities::dispatch(
                        $this->record,
                        auth()->id()
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('🔍 Rilevamento avviato')
                        ->body('L\'AI analizzerà l\'articolo in background. Riceverai una notifica quando pronto.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('clear_detected_entities')
                ->label('🗑️ Elimina entità rilevate')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Eliminare le entità rilevate?')
                ->modalDescription('Le entità rilevate dall\'AI verranno rimosse. Non verranno toccate le entità già collegate.')
                ->action(function () {
                    $this->record->update(['detected_entities' => null]);

                    \Filament\Notifications\Notification::make()
                        ->title('🗑️ Entità rilevate eliminate')
                        ->success()
                        ->send();

                    $this->refreshFormData(['detected_entities']);
                })
                ->visible(fn () => $this->record->hasDetectedEntities()),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ArticleRevisionsWidget::class,
        ];
    }
}
