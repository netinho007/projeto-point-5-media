<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCertification extends Model
{
    protected $fillable = ['candidate_id','name','issuer','issued_at','expires_at','credential_id','credential_url','file_path'];
    protected $casts = ['issued_at' => 'date', 'expires_at' => 'date'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
