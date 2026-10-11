<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        if (! auth()->user()?->hasRole(['super_admin', 'editor'])) {
            abort(403);
        }

        $type = $request->input('type', 'articles');
        $filename = "themedge-analytics-{$type}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'articles') {
                fputcsv($handle, ['ID', 'Titolo', 'Slug', 'Autore', 'Categoria', 'Views', 'Pubblicato']);
                fputcsv($handle, []);

                Article::where('status', 'published')
                    ->with(['author', 'category'])
                    ->orderByDesc('views')
                    ->chunk(100, function ($articles) use ($handle) {
                        foreach ($articles as $a) {
                            fputcsv($handle, [
                                $a->id,
                                $a->title,
                                $a->slug,
                                $a->author?->name ?? '—',
                                $a->category?->name ?? '—',
                                $a->views,
                                $a->published_at?->format('d/m/Y H:i'),
                            ]);
                        }
                    });
            } elseif ($type === 'authors') {
                fputcsv($handle, ['ID', 'Nome', 'Email', 'Job Title', 'Articoli', 'Views Totali', 'Views Medie']);
                fputcsv($handle, []);

                $authors = User::withCount(['articles' => fn ($q) => $q->where('status', 'published')])
                    ->withSum(['articles' => fn ($q) => $q->where('status', 'published')], 'views')
                    ->whereHas('articles', fn ($q) => $q->where('status', 'published'))
                    ->orderByDesc('articles_sum_views')
                    ->get();

                foreach ($authors as $a) {
                    fputcsv($handle, [
                        $a->id,
                        $a->name,
                        $a->email,
                        $a->job_title ?? '—',
                        $a->articles_count,
                        $a->articles_sum_views ?? 0,
                        $a->articles_count > 0 ? round(($a->articles_sum_views ?? 0) / $a->articles_count) : 0,
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
