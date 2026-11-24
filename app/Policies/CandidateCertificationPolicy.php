<?php

namespace App\Policies;

use App\Models\CandidateCertification;
use App\Models\User;

class CandidateCertificationPolicy
{
    public function view(User $user, CandidateCertification $certification): bool
    {
        return $user->candidate?->id === $certification->candidate_id;
    }

    public function delete(User $user, CandidateCertification $certification): bool
    {
        return $this->view($user, $certification);
    }
}
