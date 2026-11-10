<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\SiteVisit;
use App\Models\Vacancy;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCandidates = Candidate::count();
        $totalApplications = Application::count();
        $openVacancies = Vacancy::where('status', 'open')->count();
        $waitingAnalysis = Application::whereDoesntHave('analysis', fn ($query) => $query->where('status', 'completed'))->count();
        $talentPoolCount = Candidate::where('talent_pool', true)->count();
        $unreadMessages = CandidateMessage::where('sender_role','candidate')->whereNull('read_at')->count();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyVisitors = SiteVisit::whereBetween('created_at', [$monthStart, $monthEnd])
            ->distinct()
            ->count('visitor_token');
        $monthlyResumes = Application::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        $monthlyApproved = Application::where('status', 'approved')->whereBetween('updated_at', [$monthStart, $monthEnd])->count();
        $upcomingInterviewCount = Interview::where('scheduled_at', '>=', now())->where('scheduled_at', '<=', now()->addDays(14))->count();

        $mailMode = (string) config('mail.default', 'log');
        $mailReady = $mailMode !== 'log' && filled(config('mail.from.address'));
        $smsReady = filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.verify_sid'));

        $upcomingInterviews = Interview::where('scheduled_at', '>=', now())
            ->where('scheduled_at', '<=', now()->addDays(14))
            ->with(['application.candidate', 'application.vacancy'])
            ->orderBy('scheduled_at')
            ->limit(6)
            ->get();

        $recentApplications = Application::with(['candidate', 'vacancy', 'analysis'])
            ->latest()
            ->limit(8)
            ->get();

        $statusCounts = Application::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', compact(
            'totalCandidates',
            'totalApplications',
            'openVacancies',
            'waitingAnalysis',
            'talentPoolCount',
            'unreadMessages',
            'monthlyVisitors',
            'monthlyResumes',
            'monthlyApproved',
            'upcomingInterviewCount',
            'mailMode',
            'mailReady',
            'smsReady',
            'upcomingInterviews',
            'recentApplications',
            'statusCounts'
        ));
    }
}
