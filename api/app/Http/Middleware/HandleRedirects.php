<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Esegui la richiesta
        $response = $next($request);

        // Se la risposta è 404, cerca un redirect
        if ($response->getStatusCode() === 404) {
            $path = '/' . ltrim($request->path(), '/');

            $redirect = Redirect::findForPath($path);

            if ($redirect) {
                // Aggiorna statistiche
                $redirect->increment('hits');
                $redirect->update(['last_hit_at' => now()]);

                // Costruisci destinazione
                $to = $redirect->to;
                if (! str_starts_with($to, 'http://') && ! str_starts_with($to, 'https://')) {
                    $to = url($to);
                }

                return redirect()->to($to, $redirect->status_code);
            }
        }

        return $response;
    }
}
