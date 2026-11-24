<?php

namespace App\Models;

use App\Notifications\ResetPasswordPtNotification;
use App\Notifications\VerifyEmailPtNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailPtNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordPtNotification($token));
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    public function applicationNotes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class);
    }

    public function adminActivityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class);
    }

    public function favoriteCandidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'favorite_candidates')->withTimestamps();
    }

    public function sentCandidateMessages(): HasMany
    {
        return $this->hasMany(CandidateMessage::class, 'sender_user_id');
    }

    public function adminSavedFilters(): HasMany
    {
        return $this->hasMany(AdminSavedFilter::class);
    }
}
