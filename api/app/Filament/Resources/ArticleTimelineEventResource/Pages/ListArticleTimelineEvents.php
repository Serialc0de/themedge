<?php

namespace App\Filament\Resources\ArticleTimelineEventResource\Pages;

use App\Filament\Resources\ArticleTimelineEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticleTimelineEvents extends ListRecords
{
    protected static string $resource = ArticleTimelineEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
