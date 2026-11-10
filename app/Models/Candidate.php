<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'user_id','name','email','phone','city','state','headline','preferred_area','linkedin_url','portfolio_url','bio',
        'resume_path','resume_original_name','resume_mime','resume_size','talent_pool','talent_pool_consent_at','privacy_consent_at','profile_completed_at',
        'profile_photo_path','current_role','desired_role','years_experience','availability','work_mode','salary_expectation',
        'phone_verified_at','phone_verification_hash','phone_verification_expires_at','phone_verification_attempts',
        'contact_preferences','account_active','deletion_requested_at','talent_pool_expires_at',
        'github_url','website_url','behance_url','contract_preference','travel_availability','relocation_availability',
    ];

    protected $casts = [
        'talent_pool' => 'boolean',
        'talent_pool_consent_at' => 'datetime',
        'privacy_consent_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'phone_verification_expires_at' => 'datetime',
        'contact_preferences' => 'array',
        'account_active' => 'boolean',
        'deletion_requested_at' => 'datetime',
        'talent_pool_expires_at' => 'datetime',
        'salary_expectation' => 'decimal:2',
        'travel_availability' => 'boolean',
        'relocation_availability' => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function applications(): HasMany { return $this->hasMany(Application::class); }
    public function skills(): BelongsToMany { return $this->belongsToMany(Skill::class)->withPivot(['source','confidence'])->withTimestamps(); }
    public function notifications(): HasMany { return $this->hasMany(CandidateNotification::class)->latest(); }
    public function languages(): HasMany { return $this->hasMany(CandidateLanguage::class)->latest(); }
    public function certifications(): HasMany { return $this->hasMany(CandidateCertification::class)->latest(); }
    public function courses(): HasMany { return $this->hasMany(CandidateCourse::class)->latest(); }
    public function experiences(): HasMany { return $this->hasMany(CandidateExperience::class)->orderByDesc('current')->orderByDesc('start_date'); }
    public function documents(): HasMany { return $this->hasMany(CandidateDocument::class)->latest(); }
    public function educations(): HasMany { return $this->hasMany(CandidateEducation::class)->orderByDesc('current')->orderByDesc('start_date'); }
    public function projects(): HasMany { return $this->hasMany(CandidateProject::class)->latest(); }
    public function messages(): HasMany { return $this->hasMany(CandidateMessage::class)->latest(); }
    public function savedVacancies(): BelongsToMany { return $this->belongsToMany(Vacancy::class, 'saved_vacancies')->withTimestamps(); }
    public function tags(): BelongsToMany { return $this->belongsToMany(CandidateTag::class); }
    public function favoritedBy(): BelongsToMany { return $this->belongsToMany(User::class, 'favorite_candidates')->withTimestamps(); }

    public function getLocationLabelAttribute(): string
    {
        return collect([$this->city, $this->state])->filter()->implode(' / ') ?: 'Não informado';
    }

    public function getProfileCompletionAttribute(): int
    {
        $checks = [
            filled($this->name), filled($this->email), filled($this->phone), filled($this->city), filled($this->state),
            filled($this->headline), filled($this->preferred_area), filled($this->bio), filled($this->resume_path), filled($this->profile_photo_path),
            $this->experiences()->exists(), $this->educations()->exists(), $this->languages()->exists(), $this->skills()->exists(),
            $this->certifications()->exists() || $this->courses()->exists(),
        ];
        $filled = count(array_filter($checks));
        return (int) round(($filled / count($checks)) * 100);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? route('candidate.profile.photo', ['candidate' => $this->id]) : null;
    }

    public function getPhoneVerifiedAttribute(): bool
    {
        return $this->phone_verified_at !== null;
    }
}
