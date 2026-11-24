<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\CandidateCertification;
use App\Models\CandidateCourse;
use App\Models\CandidateDocument;
use App\Models\CandidateMessage;
use App\Policies\ApplicationPolicy;
use App\Policies\CandidateCertificationPolicy;
use App\Policies\CandidateCoursePolicy;
use App\Policies\CandidateDocumentPolicy;
use App\Policies\CandidateMessagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(CandidateCertification::class, CandidateCertificationPolicy::class);
        Gate::policy(CandidateCourse::class, CandidateCoursePolicy::class);
        Gate::policy(CandidateDocument::class, CandidateDocumentPolicy::class);
        Gate::policy(CandidateMessage::class, CandidateMessagePolicy::class);
    }
}
