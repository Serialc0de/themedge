<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HighlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $readerToken = $request->header('X-Reader-Token');
        $isOwner = $readerToken && $this->reader && hash_equals($this->reader->token, $readerToken);

        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'text_fragment' => $this->text_fragment,
            'context_before' => $this->context_before,
            'context_after' => $this->context_after,
            'start_offset' => $this->start_offset,
            'end_offset' => $this->end_offset,
            'color' => $this->color,
            'note' => $this->note,
            'is_public' => $this->is_public,
            'is_mine' => $isOwner,
            'reactions_count' => $this->reactions_count,
            'reader' => $this->when($this->is_public, function () {
                return [
                    'display_name' => $this->reader?->display_name ?? 'Lettore anonimo',
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
