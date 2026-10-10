<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';
    protected static ?string $navigationLabel = 'Redirect';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'Redirect';
    protected static ?string $pluralModelLabel = 'Redirect';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Regola di redirect')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('from')
                        ->label('Da (URL vecchio)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->prefix('/')
                        ->helperText('Es: articolo/vecchio-slug (senza https://themedge.com)')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('to')
                        ->label('A (URL nuovo)')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Può essere assoluto (https://...) o relativo (/articolo/nuovo-slug)')
                        ->columnSpan(1),

                    Forms\Components\Select::make('status_code')
                        ->label('Tipo redirect')
                        ->options([
                            301 => '301 — Permanente (SEO)',
                            302 => '302 — Temporaneo',
                            307 => '307 — Temporaneo (preserva metodo)',
                            308 => '308 — Permanente (preserva metodo)',
                        ])
                        ->default(301)
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Attivo')
                        ->default(true),

                    Forms\Components\Textarea::make('note')
                        ->label('Note interne')
                        ->rows(2)
                        ->placeholder('Es: "Vecchio URL di un articolo spostato"')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('from')
                    ->label('Da')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->copyable()
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('to')
                    ->label('A')
                    ->searchable()
                    ->limit(40)
                    ->copyable()
                    ->icon('heroicon-m-arrow-right-end-on-rectangle')
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_code')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        301, 308 => 'success',
                        302, 307 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('hits')
                    ->label('Hit')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 100 ? 'success' : ($state > 10 ? 'warning' : 'gray'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_hit_at')
                    ->label('Ultimo hit')
                    ->since()
                    ->placeholder('Mai')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Stato')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo attivi')
                    ->falseLabel('Solo disattivati'),

                Tables\Filters\SelectFilter::make('status_code')
                    ->label('Tipo redirect')
                    ->options([
                        301 => '301 — Permanente',
                        302 => '302 — Temporaneo',
                        307 => '307 — Temporaneo',
                        308 => '308 — Permanente',
                    ]),

                Tables\Filters\Filter::make('used')
                    ->label('Solo usati')
                    ->query(fn (Builder $query) => $query->where('hits', '>', 0)),

                Tables\Filters\Filter::make('unused')
                    ->label('Mai usati')
                    ->query(fn (Builder $query) => $query->where('hits', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Redirect $record) => config('app.public_site_url', 'https://themedge.com') . '/' . ltrim($record->from, '/'))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('reset_hits')
                    ->label('Reset hits')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resettare i contatori?')
                    ->modalDescription('Il contatore degli hit verrà azzerato.')
                    ->visible(fn (Redirect $record) => $record->hits > 0)
                    ->action(function (Redirect $record) {
                        $record->update(['hits' => 0, 'last_hit_at' => null]);

                        \Filament\Notifications\Notification::make()
                            ->title('Contatore azzerato')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina selezionati'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_active', true)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
