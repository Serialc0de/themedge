<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikipediaChunk extends Model
{
    protected $fillable = [
        'entity_id',
        'chunk_index',
        'content',
        'embedding',
        'embedding_model',
    ];

    protected $casts = [
        'embedding' => 'array',
        'chunk_index' => 'integer',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
