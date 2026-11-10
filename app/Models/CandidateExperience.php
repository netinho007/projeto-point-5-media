<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExperience extends Model
{
    protected $fillable = ['candidate_id','company','role','start_date','end_date','current','description'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'current' => 'boolean'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
