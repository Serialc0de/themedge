<?php

namespace App\Filament\Resources\ArticleFactCheckResource\Pages;

use App\Filament\Resources\ArticleFactCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticleFactCheck extends EditRecord
{
    protected static string $resource = ArticleFactCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
