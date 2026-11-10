<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateProject extends Model
{
    protected $fillable = ['candidate_id','name','description','technologies','project_url','repository_url'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
