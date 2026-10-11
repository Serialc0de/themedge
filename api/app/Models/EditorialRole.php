<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EditorialRole extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
