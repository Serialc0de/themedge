<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HighlightStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Cast difensivo: accetta sia array che stdClass
        $data = is_array($this->resource)
            ? $this->resource
            : (array) $this->resource;

        return [
            'article_id' => $data['article_id'] ?? null,
            'total_highlights' => $data['total_highlights'] ?? 0,
            'total_public' => $data['total_public'] ?? 0,
            'unique_readers' => $data['unique_readers'] ?? 0,
            'by_color' => $data['by_color'] ?? [],
            'top_fragments' => $data['top_fragments'] ?? [],
            'heatmap' => $data['heatmap'] ?? [],
        ];
    }
}
