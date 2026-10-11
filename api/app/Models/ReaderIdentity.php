<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReaderIdentity extends Model
{
    protected $fillable = [
        'token',
        'display_name',
        'email',
        'first_ip',
        'last_ip',
        'user_agent',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'first_ip',
        'last_ip',
        'user_agent',
    ];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ReaderHighlight::class, 'reader_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(HighlightReaction::class, 'reader_id');
    }

    public function touchLastSeen(?string $ip = null): void
    {
        $this->last_seen_at = now();
        if ($ip) {
            $this->last_ip = $ip;
        }
        $this->saveQuietly();
    }
}
