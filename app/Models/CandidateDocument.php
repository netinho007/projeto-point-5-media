<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDocument extends Model
{
    protected $fillable = ['candidate_id','type','title','file_path','original_name','mime','size'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
}
