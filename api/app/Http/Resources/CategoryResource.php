<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'emoji' => $this->emoji,
            'description' => $this->description,
            'color' => $this->color,
            'image' => $this->image,
            'order' => $this->order,
            'articles_count' => $this->whenCounted('articles'),
        ];
    }
}
