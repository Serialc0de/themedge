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

        Actions\ActionGroup::make([
            Actions\Action::make('generate_ai_summary')
                ->label('✨ Genera TL;DR')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Genera TL;DR')
                ->modalDescription('Verrà generato un riassunto in background con AI. Potrebbe richiedere 2-4 minuti. Riceverai una notifica quando è pronto.')
                ->modalSubmitActionLabel('Genera')
                ->action(function () {
                    \App\Jobs\GenerateArticleSummary::dispatch($this->record, auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->title('✨ Generazione avviata')
                        ->body('Il TL;DR verrà generato in background.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('delete_ai_summary')
                ->label('🗑️ Elimina TL;DR')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
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
                ->modalDescription('L\'AI analizzerà l\'articolo e rileverà le entità. Potrebbe richiedere 3-4 minuti.')
                ->modalSubmitActionLabel('Avvia')
                ->action(function () {
                    \App\Jobs\DetectArticleEntities::dispatch($this->record, auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->title('🔍 Rilevamento avviato')
                        ->body('L\'AI analizzerà l\'articolo in background.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('clear_detected_entities')
                ->label('🗑️ Elimina entità rilevate')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['detected_entities' => null]);
                    \Filament\Notifications\Notification::make()
                        ->title('🗑️ Entità rilevate eliminate')
                        ->success()
                        ->send();
                    $this->refreshFormData(['detected_entities']);
                })
                ->visible(fn () => $this->record->hasDetectedEntities()),

            Actions\Action::make('optimize_seo')
                ->label('🎯 Ottimizza SEO (AI)')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Ottimizza SEO con AI')
                ->modalDescription('L\'AI analizzerà l\'articolo e suggerirà: titolo SEO, meta description, keyword, hashtag.')
                ->modalSubmitActionLabel('Analizza')
                ->action(function () {
                    \App\Jobs\GenerateSeoSuggestions::dispatch($this->record, auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->title('🎯 Analisi SEO avviata')
                        ->body('L\'AI analizzerà l\'articolo in background.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('clear_seo_suggestions')
                ->label('🗑️ Elimina suggerimenti SEO')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'ai_seo_suggestions' => null,
                        'ai_seo_generated_at' => null,
                        'ai_seo_model' => null,
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->title('🗑️ Suggerimenti SEO eliminati')
                        ->success()
                        ->send();
                    $this->refreshFormData(['ai_seo_suggestions']);
                })
                ->visible(fn () => $this->record->hasSeoSuggestions()),

            Actions\Action::make('detect_claims')
                ->label('✅ Fact-check automatico (AI)')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Fact-check automatico')
                ->modalDescription('L\'AI estrarrà le affermazioni verificabili dall\'articolo e le proporrà per la verifica. Nessun fact-check verrà pubblicato automaticamente: l\'editore decide sempre.')
                ->modalSubmitActionLabel('Avvia analisi')
                ->action(function () {
                    \App\Jobs\DetectArticleClaims::dispatch(
                        $this->record,
                        auth()->id()
                    );

                    \Filament\Notifications\Notification::make()
                        ->title('✅ Analisi avviata')
                        ->body('L\'AI analizzerà l\'articolo in background. Riceverai una notifica quando pronto.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('clear_claims')
                ->label('🗑️ Elimina claim rilevati')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->claims()->where('status', 'pending')->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('🗑️ Claim eliminati')
                        ->success()
                        ->send();
                    $this->refreshFormData(['claims']);
                })
                ->visible(fn () => $this->record->hasPendingClaims()),
        ])
            ->label('🤖 AI Actions')
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->button(),

        Actions\DeleteAction::make()->label('Elimina'),
    ];
}

    protected function getFooterWidgets(): array
    {
        return [
            ArticleRevisionsWidget::class,
        ];
    }
}
