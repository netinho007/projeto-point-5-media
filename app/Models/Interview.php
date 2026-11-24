<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'application_id',
        'scheduled_at',
        'format',
        'location',
        'meeting_url',
        'candidate_instructions',
        'internal_notes',
        'created_by',
        'candidate_response',
        'candidate_response_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'candidate_response_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
