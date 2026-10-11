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

class RobotsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Robots.txt';
    protected static ?string $navigationGroup = 'SEO';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Robots.txt';
    protected static ?string $slug = 'seo/robots';
    protected static string $view = 'filament.pages.robots';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'robots_txt' => SeoSetting::get('robots_txt') ?: $this->getDefaultRobots(),
        ]);
    }

    public function getDefaultRobots(): string
    {
        $base = config('app.public_site_url', 'https://themedge.com');

        return "User-agent: *\nDisallow: /admin/\nDisallow: /api/\nDisallow: /livewire/\n\nAllow: /\n\nSitemap: {$base}/sitemap.xml";
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contenuto robots.txt')
                    ->description('Modifica il contenuto del file robots.txt')
                    ->schema([
                        Forms\Components\Textarea::make('robots_txt')
                            ->label('')
                            ->rows(20)
                            ->required()
                            ->extraInputAttributes(['style' => 'font-family: monospace; font-size: 13px;']),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SeoSetting::set('robots_txt', $data['robots_txt']);
        \Illuminate\Support\Facades\Cache::forget('robots_txt');

        Notification::make()
            ->title('Robots.txt salvato')
            ->success()
            ->send();
    }

    public function resetRobots(): void
    {
        $this->form->fill([
            'robots_txt' => $this->getDefaultRobots(),
        ]);

        Notification::make()
            ->title('Reset completato')
            ->info()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_robots')
                ->label('Ripristina default')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action('resetRobots'),
        ];
    }
}
