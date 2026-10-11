<?php

namespace App\Filament\Pages;

use App\Models\EditorialRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class EditorialTeamPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Squadra editoriale';
    protected static ?string $navigationGroup = 'Redazione';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Squadra editoriale';
    protected static ?string $slug = 'redazione/squadra';
    protected static string $view = 'filament.pages.editorial-team';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_users')
                ->label('Gestisci utenti')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('primary')
                ->url('/admin/users'),

            Action::make('manage_roles')
                ->label('Gestisci ruoli editoriali')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url('/admin/editorial-roles'),
        ];
    }
}
