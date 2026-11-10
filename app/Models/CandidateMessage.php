<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateMessage extends Model
{
    protected $fillable = ['candidate_id','application_id','sender_user_id','sender_role','subject','message','read_at','attachment_path','attachment_name','attachment_mime'];
    protected $casts = ['read_at' => 'datetime'];
    public function candidate(): BelongsTo { return $this->belongsTo(Candidate::class); }
    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
}
