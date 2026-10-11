<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, string $type = 'string'): void
    {
        // Normalizza il valore in base al tipo
        $normalizedValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => is_array($value) ? json_encode($value) : (string) $value,
            default => $value === null ? '' : (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $normalizedValue,
                'type' => $type,
            ]
        );
    }
}
