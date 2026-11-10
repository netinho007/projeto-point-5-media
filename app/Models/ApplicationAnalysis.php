<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAnalysis extends Model
{
    protected $fillable = [
        'application_id',
        'status',
        'match_score',
        'summary',
        'extracted_text',
        'skills',
        'experience',
        'education',
        'matched_requirements',
        'missing_requirements',
        'languages',
        'certifications',
        'profile_highlights',
        'error_message',
        'analyzed_at',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'skills' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'matched_requirements' => 'array',
        'missing_requirements' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'profile_highlights' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
