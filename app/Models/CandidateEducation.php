<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateEducation extends Model
{
    protected $table = 'candidate_educations';
    protected $fillable = ['candidate_id','institution','course','degree','start_date','end_date','current'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','current'=>'boolean'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
