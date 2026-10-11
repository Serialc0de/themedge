<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonySanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitizza l'HTML degli articoli secondo una allow-list configurabile.
 *
 * API symfony/html-sanitizer v7:
 *   - allowElement(string $tag, array|string $allowedAttributes)
 *   - allowAttribute(string $attr, array|string $allowedElements)
 *   - allowLinkHosts(?array), allowMediaHosts(?array)
 *   - withAttributeSanitizer(AttributeSanitizerInterface)
 */
class HtmlSanitizer
{
    protected SymfonySanitizer $sanitizer;

    /** @var string[] */
    protected array $allowedIframeHosts = [];

    /** @var string[] */
    protected array $blockedIframeHosts = [];

    public function __construct()
    {
        $this->blockedIframeHosts = config('embeds.sanitize.blocked_iframe_hosts', []);
        foreach (config('embeds.providers', []) as $p) {
            foreach ($p['iframe_hosts'] ?? [] as $h) {
                $this->allowedIframeHosts[] = strtolower($h);
            }
        }

        $this->sanitizer = $this->buildSanitizer();
    }

    protected function buildSanitizer(): SymfonySanitizer
    {
        $allowedTags = config('embeds.sanitize.allowed_tags', []);
        $allowedAttrs = config('embeds.sanitize.allowed_attributes', []);

        $config = new HtmlSanitizerConfig();

        // 1) Elementi sicuri di default (p, h1-h6, strong, a, ul, li, img, ecc.)
        $config = $config->allowSafeElements();

        // 2) URL: solo https, niente link relativi, niente media relativi
        $config = $config
            ->forceHttpsUrls(true)
            ->allowRelativeLinks(false)
            ->allowRelativeMedias(false)
            ->allowLinkSchemes(['https', 'mailto', 'tel'])
            ->allowMediaSchemes(['https']);

        // 3) Tag extra della allow-list (iframe, video, audio, figure, ecc.)
        //    con i loro attributi specifici passati direttamente.
        foreach ($allowedTags as $tag) {
            $attrs = $allowedAttrs[$tag] ?? null;
            if ($attrs === null) {
                // non forziamo attributi: usa default safe
                $config = $config->allowElement($tag);
            } else {
                $config = $config->allowElement($tag, $attrs);
            }
        }

        // 4) Attributi wildcard "*" -> applicati a tutti gli elementi
        foreach ($allowedAttrs['*'] ?? [] as $attr) {
            $config = $config->allowAttribute($attr, '*');
        }

        // 5) Attributi specifici per tag extra (non già gestiti al punto 3)
        foreach ($allowedAttrs as $tag => $attrs) {
            if ($tag === '*') {
                continue;
            }
            // Evita di riapplicare per tag già gestiti al punto 3:
            if (in_array($tag, $allowedTags, true)) {
                continue;
            }
            foreach ($attrs as $attr) {
                $config = $config->allowAttribute($attr, $tag);
            }
        }

        return new SymfonySanitizer($config);
    }

    public function sanitize(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $clean = $this->sanitizer->sanitize($html);
        $clean = $this->stripDangerousIframes($clean);

        return $clean;
    }

    public function sanitizeForOutput(?string $html): string
    {
        return $this->sanitize($html);
    }

    /**
     * Rimuove iframe verso host interni o non in allow-list.
     * Applicato DOPO il sanitizer principale, così src è già preservato.
     */
    protected function stripDangerousIframes(string $html): string
    {
        return preg_replace_callback(
            '#<iframe\b([^>]*)>(.*?)</iframe>#is',
            function ($m) {
                $attrs = $m[1];
                if (!preg_match('#\bsrc\s*=\s*(["\'])([^"\']+)\1#i', $attrs, $sm)) {
                    return '<!-- iframe removed: missing src -->';
                }
                $src = $sm[2];
                $host = strtolower(parse_url($src, PHP_URL_HOST) ?? '');

                if ($host === '') {
                    return '<!-- iframe removed: invalid host -->';
                }

                // Blocco host interni
                foreach ($this->blockedIframeHosts as $blocked) {
                    $b = strtolower(rtrim($blocked, '.'));
                    if ($b === '') continue;
                    if ($host === $b || str_ends_with($host, '.' . $b) || str_starts_with($host, $b)) {
                        return '<!-- iframe removed: internal host -->';
                    }
                }

                // Allow-list
                if (!empty($this->allowedIframeHosts)) {
                    $ok = false;
                    foreach ($this->allowedIframeHosts as $allowed) {
                        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                            $ok = true;
                            break;
                        }
                    }
                    if (!$ok) {
                        return '<!-- iframe removed: host not allowed -->';
                    }
                }

                return $m[0];
            },
            $html
        ) ?? $html;
    }
}
