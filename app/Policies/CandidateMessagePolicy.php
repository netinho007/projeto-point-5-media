<?php

namespace App\Policies;

use App\Models\CandidateMessage;
use App\Models\User;

class CandidateMessagePolicy
{
    public function view(User $user, CandidateMessage $message): bool
    {
        return $user->candidate?->id === $message->candidate_id;
    }
}
