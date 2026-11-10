<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    public const STATUSES = [
        'received' => 'Currículo recebido',
        'reviewing' => 'Em análise',
        'shortlisted' => 'Pré-selecionado',
        'interview' => 'Entrevista agendada',
        'interview_done' => 'Entrevista realizada',
        'technical_test' => 'Teste técnico',
        'decision' => 'Aguardando decisão',
        'approved' => 'Aprovado',
        'not_selected' => 'Não selecionado',
        'withdrawn' => 'Retirada pelo candidato',
        'archived' => 'Processo encerrado',
    ];

    public const STATUS_DESCRIPTIONS = [
        'received' => 'Recebemos sua candidatura e ela já está registrada no processo.',
        'reviewing' => 'O time de recrutamento está revisando seu perfil e currículo.',
        'shortlisted' => 'Seu perfil avançou para a próxima etapa do processo.',
        'interview' => 'Uma entrevista foi agendada. Confira os detalhes no portal.',
        'interview_done' => 'A etapa de entrevista foi concluída.',
        'technical_test' => 'Você avançou para uma etapa técnica do processo.',
        'decision' => 'O processo está em fase de decisão.',
        'approved' => 'O processo foi concluído com avanço para contratação. O time entrará em contato.',
        'not_selected' => 'Seu processo foi encerrado para esta oportunidade.',
        'withdrawn' => 'Você retirou sua candidatura desta oportunidade.',
        'archived' => 'Esta candidatura não possui novas etapas previstas no momento.',
    ];

    protected $fillable = [
        'candidate_id',
        'vacancy_id',
        'resume_path',
        'resume_original_name',
        'resume_mime',
        'resume_size',
        'status',
        'analyzed_at',
        'consent_at',
        'cover_letter',
        'referral_name',
        'source',
        'withdrawn_at',
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'consent_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(ApplicationAnalysis::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->oldest();
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(CandidateNotification::class);
    }

    public function interview(): HasOne
    {
        return $this->hasOne(Interview::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusDescriptionAttribute(): string
    {
        return self::STATUS_DESCRIPTIONS[$this->status] ?? '';
    }

    public function getProgressPercentAttribute(): int
    {
        return match ($this->status) {
            'received' => 15,
            'reviewing' => 35,
            'shortlisted' => 60,
            'interview' => 70,
            'interview_done' => 78,
            'technical_test' => 84,
            'decision' => 92,
            'approved', 'not_selected', 'withdrawn', 'archived' => 100,
            default => 10,
        };
    }
}
