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

class SeoSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Impostazioni SEO';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Impostazioni SEO';
    protected static ?string $slug = 'seo/settings';
    protected static string $view = 'filament.pages.seo-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            // Generali
            'site_name' => SeoSetting::get('site_name', 'Themedge'),
            'site_tagline' => SeoSetting::get('site_tagline', ''),
            'site_description' => SeoSetting::get('site_description', ''),
            'meta_title_template' => SeoSetting::get('meta_title_template', '%s — Themedge'),
            'default_og_image' => SeoSetting::get('default_og_image', ''),
            'default_locale' => SeoSetting::get('default_locale', 'it_IT'),

            // Social
            'twitter_handle' => SeoSetting::get('twitter_handle', ''),
            'facebook_url' => SeoSetting::get('facebook_url', ''),
            'instagram_url' => SeoSetting::get('instagram_url', ''),
            'linkedin_url' => SeoSetting::get('linkedin_url', ''),

            // Analytics
            'google_analytics_id' => SeoSetting::get('google_analytics_id', ''),
            'plausible_domain' => SeoSetting::get('plausible_domain', ''),
            'umami_website_id' => SeoSetting::get('umami_website_id', ''),

            // Verifiche
            'google_site_verification' => SeoSetting::get('google_site_verification', ''),
            'bing_site_verification' => SeoSetting::get('bing_site_verification', ''),
            'yandex_verification' => SeoSetting::get('yandex_verification', ''),

            // Robots
            'robots_index' => SeoSetting::get('robots_index', '1') === '1',
            'robots_follow' => SeoSetting::get('robots_follow', '1') === '1',
            'robots_max_image_preview' => SeoSetting::get('robots_max_image_preview', 'large'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Generali')
                    ->description('Informazioni base del sito')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Nome sito')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(200),

                        Forms\Components\Textarea::make('site_description')
                            ->label('Meta description default')
                            ->rows(3)
                            ->maxLength(300)
                            ->helperText('Usata quando una pagina non ha una meta description specifica')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('meta_title_template')
                            ->label('Template titolo')
                            ->helperText('%s = titolo pagina. Es. "%s — Themedge"')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('default_locale')
                            ->label('Locale')
                            ->default('it_IT')
                            ->maxLength(10),

                        Forms\Components\TextInput::make('default_og_image')
                            ->label('URL immagine OG default')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Immagine mostrata quando condividi pagine senza immagine')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Social')
                    ->description('Profili social ufficiali')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('twitter_handle')
                            ->label('Twitter/X handle')
                            ->prefix('@')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Analytics')
                    ->description('Tracking e analytics')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics ID')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('Measurement ID di Google Analytics 4')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('plausible_domain')
                            ->label('Plausible domain')
                            ->placeholder('themedge.com')
                            ->helperText('Solo il dominio (senza https://)')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('umami_website_id')
                            ->label('Umami Website ID')
                            ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
                            ->helperText('ID del sito su Umami')
                            ->maxLength(100),
                    ]),

                Forms\Components\Section::make('Verifiche Search Engine')
                    ->description('Meta tag di verifica per i motori di ricerca')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('google_site_verification')
                            ->label('Google Search Console')
                            ->placeholder('google-site-verification=...')
                            ->helperText('Token di verifica Google')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bing_site_verification')
                            ->label('Bing Webmaster')
                            ->placeholder('msvalidate.01=...')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('yandex_verification')
                            ->label('Yandex')
                            ->placeholder('yandex-verification=...')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Robots')
                    ->description('Controllo indicizzazione')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('robots_index')
                            ->label('Permetti indicizzazione (index)')
                            ->helperText('Se disattivato, i motori non indicizzeranno le pagine')
                            ->default(true),

                        Forms\Components\Toggle::make('robots_follow')
                            ->label('Permetti follow link')
                            ->helperText('Se disattivato, i motori non seguiranno i link')
                            ->default(true),

                        Forms\Components\Select::make('robots_max_image_preview')
                            ->label('Max image preview')
                            ->options([
                                'none' => 'Nessuna',
                                'standard' => 'Standard',
                                'large' => 'Grande',
                            ])
                            ->default('large'),
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
            ->title('Impostazioni salvate')
            ->body('Le impostazioni SEO sono state aggiornate.')
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
