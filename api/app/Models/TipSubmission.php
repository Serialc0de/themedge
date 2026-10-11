<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipSubmission extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
        'attachment_path', 'category', 'status', 'internal_notes',
        'assigned_to', 'ip_address',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
