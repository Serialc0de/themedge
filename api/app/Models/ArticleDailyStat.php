<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleDailyStat extends Model
{
    protected $fillable = ['date', 'article_id', 'views', 'comments', 'unique_views'];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'comments' => 'integer',
        'unique_views' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
