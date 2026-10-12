<?php

namespace App\Filament\Resources\ArticleTimelineEventResource\Pages;

use App\Filament\Resources\ArticleTimelineEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticleTimelineEvent extends EditRecord
{
    protected static string $resource = ArticleTimelineEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
