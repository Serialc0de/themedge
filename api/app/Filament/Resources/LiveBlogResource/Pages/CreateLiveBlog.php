<?php

namespace App\Filament\Resources\LiveBlogResource\Pages;

use App\Filament\Resources\LiveBlogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLiveBlog extends CreateRecord
{
    protected static string $resource = LiveBlogResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
