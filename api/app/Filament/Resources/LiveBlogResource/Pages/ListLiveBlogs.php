<?php

namespace App\Filament\Resources\LiveBlogResource\Pages;

use App\Filament\Resources\LiveBlogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiveBlogs extends ListRecords
{
    protected static string $resource = LiveBlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuova diretta'),
        ];
    }
}
