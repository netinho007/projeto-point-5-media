<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    protected $casts = ['published_at' => 'datetime', 'closes_at' => 'datetime', 'referral_enabled' => 'boolean'];
    protected $fillable = [
        'title',
        'department',
        'location',
        'description',
        'requirements',
        'employment_type',
        'status',
        'work_mode',
        'published_at',
        'closes_at',
        'referral_enabled',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->where(function ($q) { $q->whereNull('published_at')->orWhere('published_at', '<=', now()); })
            ->where(function ($q) { $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()); });
    }

    public function savedBy(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'saved_vacancies')->withTimestamps();
    }
}

