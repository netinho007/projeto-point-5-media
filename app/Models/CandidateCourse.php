<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateCourse extends Model
{
    protected $fillable = ['candidate_id','name','institution','workload_hours','completed_at','certificate_path'];
    protected $casts = ['completed_at' => 'date'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
