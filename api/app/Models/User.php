<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'is_active',
        'last_login_at',
        'job_title',
        'twitter_handle',
        'editorial_role_id',
        'show_in_editorial_board',
        'board_order',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'show_in_editorial_board' => 'boolean',
        'board_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'job_title'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName) => "Utente {$eventName}: {$this->name}")
            ->useLogName('user');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar) {
            return Storage::disk('public')->url($this->avatar);
        }

        return null;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function editorialRole(): BelongsTo
    {
        return $this->belongsTo(EditorialRole::class);
    }
}
