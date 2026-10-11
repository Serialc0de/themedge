<?php

namespace App\Services;

use App\Services\EmbedProviders\AbstractProvider;
use Illuminate\Support\Facades\Cache;

class OEmbedResolver
{
    /** @var AbstractProvider[] */
    protected array $providers = [];

    public function __construct()
    {
        foreach (config('embeds.providers', []) as $key => $cfg) {
            $class = $cfg['class'] ?? null;
            if ($class && class_exists($class)) {
                $this->providers[$key] = app($class);
            }
        }
    }

    /**
     * Risolve un URL in HTML embed. Ritorna array con provider, html, aspect.
     */
    public function resolve(string $url): ?array
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $cacheKey = config('embeds.cache.prefix', 'oembed:') . md5($url);
        if (config('embeds.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) return $cached;
        }

        foreach ($this->providers as $key => $provider) {
            if (!$provider->matches($url)) continue;

            $html = $provider->resolve($url);
            if ($html === null) continue;

            $result = [
                'provider' => $key,
                'name' => $provider->name(),
                'url' => $url,
                'html' => $html,
                'aspect' => $provider->aspect(),
            ];

            if (config('embeds.cache.enabled', true)) {
                Cache::put($cacheKey, $result, config('embeds.cache.ttl', 86400));
            }

            return $result;
        }

        return null;
    }

    /**
     * Lista provider per UI (etichette, patterns).
     */
    public function providers(): array
    {
        $out = [];
        foreach (config('embeds.providers', []) as $key => $cfg) {
            $out[$key] = [
                'name' => $cfg['name'] ?? $key,
                'patterns' => $cfg['patterns'] ?? [],
            ];
        }
        return $out;
    }
}
