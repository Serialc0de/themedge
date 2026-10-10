<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use App\Models\MediaAsset;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('upload')
                ->label('Carica file')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Carica nuovi file nella libreria')
                ->modalSubmitActionLabel('Carica')
                ->form([
                    Forms\Components\FileUpload::make('files')
                        ->label('Seleziona file')
                        ->multiple()
                        ->image()
                        ->imageEditor()
                        ->directory('tmp-uploads')
                        ->disk('public')
                        ->maxFiles(20)
                        ->maxSize(10240)
                        ->required()
                        ->storeFileNamesIn('original_names')
                        ->helperText('Fino a 20 file (max 10 MB cad.), formati: JPG, PNG, WebP, GIF, SVG'),
                ])
                ->action(function (array $data) {
                    $files = $data['files'] ?? [];
                    $originalNames = $data['original_names'] ?? [];
                    $count = 0;

                    foreach ($files as $key => $relativePath) {
                        $fullPath = storage_path('app/public/' . $relativePath);
                        if (! file_exists($fullPath)) {
                            continue;
                        }

                        $title = $originalNames[$key] ?? basename($relativePath);

                        $asset = MediaAsset::create([
                            'title' => pathinfo($title, PATHINFO_FILENAME),
                        ]);

                        $asset->addMedia($fullPath)
                            ->usingFileName(basename($relativePath))
                            ->toMediaCollection('library');

                        // Rimuovi il file temporaneo
                        @unlink($fullPath);

                        $count++;
                    }

                    Notification::make()
                        ->title('File caricati')
                        ->body("$count file aggiunti alla libreria media.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
