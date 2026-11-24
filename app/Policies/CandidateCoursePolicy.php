<?php

namespace App\Policies;

use App\Models\CandidateCourse;
use App\Models\User;

class CandidateCoursePolicy
{
    public function view(User $user, CandidateCourse $course): bool
    {
        return $user->candidate?->id === $course->candidate_id;
    }

    public function delete(User $user, CandidateCourse $course): bool
    {
        return $this->view($user, $course);
    }
}
