<?php

namespace App\Filament\Pages;

use App\Models\SeoSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BreakingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Breaking News';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Breaking News — Impostazioni';
    protected static ?string $slug = 'seo/breaking';
    protected static string $view = 'filament.pages.breaking-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'editor']) ?? false;
    }

    public function mount(): void
    {
        $pages = SeoSetting::get('breaking_pages', []);

        $this->form->fill([
            'breaking_display_mode' => SeoSetting::get('breaking_display_mode', 'global'),
            'breaking_pages' => is_array($pages) ? $pages : [],
            'breaking_style' => SeoSetting::get('breaking_style', 'pulse_red'),
            'breaking_bg_color' => SeoSetting::get('breaking_bg_color', ''),
            'breaking_text_color' => SeoSetting::get('breaking_text_color', ''),
            'breaking_show_badge' => SeoSetting::get('breaking_show_badge', true),
            'breaking_badge_all' => SeoSetting::get('breaking_badge_all', false),
            'breaking_auto_refresh' => SeoSetting::get('breaking_auto_refresh', true),
            'breaking_refresh_interval' => SeoSetting::get('breaking_refresh_interval', 60),
            'breaking_dismissible' => SeoSetting::get('breaking_dismissible', true),
            'breaking_dismiss_hours' => SeoSetting::get('breaking_dismiss_hours', 4),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📍 Posizione banner')
                    ->description('Dove deve apparire il banner breaking news')
                    ->schema([
                        Forms\Components\Radio::make('breaking_display_mode')
                            ->label('Modalità')
                            ->options([
                                'global' => 'Top globale (tutte le pagine)',
                                'home' => 'Solo homepage',
                                'custom' => 'Personalizzato (seleziona sotto)',
                                'disabled' => 'Disabilitato',
                            ])
                            ->default('global')
                            ->live(),

                        Forms\Components\CheckboxList::make('breaking_pages')
                            ->label('Pagine specifiche')
                            ->options([
                                'home' => 'Homepage',
                                'category' => 'Categorie',
                                'tag' => 'Tag',
                                'article' => 'Articoli',
                                'author' => 'Autori',
                                'search' => 'Ricerca',
                                'static' => 'Pagine statiche (chi siamo, contatti...)',
                            ])
                            ->columns(3)
                            ->visible(fn (Forms\Get $get) => $get('breaking_display_mode') === 'custom'),
                    ]),

                Forms\Components\Section::make('🎨 Stile banner')
                    ->description('Aspetto visivo del banner')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Radio::make('breaking_style')
                            ->label('Stile')
                            ->options([
                                'pulse_red' => 'Rosso lampeggiante (default)',
                                'solid_red' => 'Rosso fisso',
                                'black_red' => 'Nero con testo rosso',
                                'yellow_warning' => 'Giallo "attenzione"',
                                'custom' => 'Personalizzato',
                            ])
                            ->default('pulse_red')
                            ->columnSpanFull()
                            ->live(),

                        Forms\Components\ColorPicker::make('breaking_bg_color')
                            ->label('Colore sfondo')
                            ->visible(fn (Forms\Get $get) => $get('breaking_style') === 'custom'),

                        Forms\Components\ColorPicker::make('breaking_text_color')
                            ->label('Colore testo')
                            ->visible(fn (Forms\Get $get) => $get('breaking_style') === 'custom'),
                    ]),

                Forms\Components\Section::make('🔔 Badge ULTIM\'ORA')
                    ->description('Badge sulle card degli articoli breaking')
                    ->schema([
                        Forms\Components\Toggle::make('breaking_show_badge')
                            ->label('Mostra badge sulle card breaking')
                            ->default(true),

                        Forms\Components\Toggle::make('breaking_badge_all')
                            ->label('Mostra badge su TUTTE le card')
                            ->helperText('Se attivo, mostra il badge anche su articoli non-breaking')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('⚙️ Comportamento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('breaking_auto_refresh')
                            ->label('Auto-refresh')
                            ->helperText('Ricarica automaticamente il banner per mostrare nuove breaking')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('breaking_refresh_interval')
                            ->label('Intervallo refresh (secondi)')
                            ->numeric()
                            ->default(60)
                            ->minValue(15)
                            ->maxValue(600)
                            ->visible(fn (Forms\Get $get) => $get('breaking_auto_refresh')),

                        Forms\Components\Toggle::make('breaking_dismissible')
                            ->label('Dismissibile (bottone ×)')
                            ->helperText('Gli utenti possono chiudere il banner')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('breaking_dismiss_hours')
                            ->label('Ore prima di riapparire')
                            ->numeric()
                            ->default(4)
                            ->minValue(0)
                            ->maxValue(168)
                            ->visible(fn (Forms\Get $get) => $get('breaking_dismissible')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_array($value) => 'json',
                default => 'string',
            };

            SeoSetting::set($key, $value, $type);
        }

        \Illuminate\Support\Facades\Cache::forget('seo_settings_all');

        Notification::make()
            ->title('Impostazioni Breaking News salvate')
            ->body('Le modifiche sono effettive al prossimo refresh.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salva impostazioni')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
    }
}
