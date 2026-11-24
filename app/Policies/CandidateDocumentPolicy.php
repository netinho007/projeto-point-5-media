<?php

namespace App\Policies;

use App\Models\CandidateDocument;
use App\Models\User;

class CandidateDocumentPolicy
{
    public function view(User $user, CandidateDocument $document): bool
    {
        return $user->candidate?->id === $document->candidate_id;
    }

    public function delete(User $user, CandidateDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
