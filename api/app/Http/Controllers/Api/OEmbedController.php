<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HtmlSanitizer;
use App\Services\OEmbedResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OEmbedController extends Controller
{
    public function __construct(
        protected OEmbedResolver $resolver,
        protected HtmlSanitizer $sanitizer,
    ) {}

    public function resolve(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        $result = $this->resolver->resolve($data['url']);

        if ($result === null) {
            return response()->json([
                'error' => 'URL non supportato o non risolvibile',
            ], 422);
        }

        // Sanitizza l'HTML generato prima di restituirlo (difesa in profondità)
        $result['html'] = $this->sanitizer->sanitize($result['html']);

        Log::info('oEmbed resolved', [
            'provider' => $result['provider'],
            'url' => $result['url'],
            'ip' => $request->ip(),
        ]);

        return response()->json($result);
    }

    public function providers()
    {
        return response()->json([
            'providers' => $this->resolver->providers(),
        ]);
    }
}
