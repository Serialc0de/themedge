<?php

namespace App\Filament\Resources\LiveBlogResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'allUpdates';
    protected static ?string $title = 'Aggiornamenti in diretta';
    protected static ?string $modelLabel = 'Aggiornamento';
    protected static ?string $pluralModelLabel = 'Aggiornamenti';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options([
                    'update' => '📝 Aggiornamento',
                    'breaking' => '🚨 Ultim\'ora',
                    'analysis' => '📊 Analisi',
                    'quote' => '💬 Dichiarazione',
                    'photo' => '📸 Foto',
                    'video' => '🎥 Video',
                    'result' => '🏆 Risultato',
                ])
                ->default('update')
                ->required()
                ->live(),

            Forms\Components\TextInput::make('title')
                ->label('Titolo (opzionale)')
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('content')
                ->label('Contenuto')
                ->required()
                ->rows(5)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('image')
                ->label('Immagine (opzionale)')
                ->image()
                ->imageEditor()
                ->directory('live-blogs/updates')
                ->disk('public')
                ->maxSize(5120)
                ->live()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('image_caption')
                ->label('Didascalia immagine')
                ->maxLength(255)
                ->visible(fn (Forms\Get $get) => ! empty($get('image')))
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('published_at')
                ->label('Pubblicato il')
                ->seconds(false)
                ->default(now()),

            Forms\Components\Toggle::make('is_pinned')
                ->label('Fissa in alto'),

            Forms\Components\Toggle::make('is_published')
                ->label('Pubblicato')
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'breaking' => '🚨 Ultim\'ora',
                        'analysis' => '📊 Analisi',
                        'quote' => '💬 Dichiarazione',
                        'photo' => '📸 Foto',
                        'video' => '🎥 Video',
                        'result' => '🏆 Risultato',
                        default => '📝 Aggiornamento',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'breaking' => 'danger',
                        'analysis' => 'info',
                        'quote' => 'purple',
                        'photo' => 'success',
                        'video' => 'pink',
                        'result' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('content')
                    ->label('Contenuto')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autore')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('📌')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pubbl.')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Quando')
                    ->since()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuovo aggiornamento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['author_id'] = Auth::id();
                        return $data;
                    })
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->title('Aggiornamento pubblicato')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
