<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactSubmission extends Model
{
    protected $fillable = [
        'site_id',
        'email',
        'subject',
        'message',
        'preferred_contact_time',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
