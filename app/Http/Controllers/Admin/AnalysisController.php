<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AdminActivityLogger;
use App\Services\ResumeAnalysisService;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::query()->with(['candidate', 'vacancy', 'analysis']);

        if ($request->input('status') === 'completed') {
            $query->whereHas('analysis', fn ($analysis) => $analysis->where('status', 'completed'));
        } elseif ($request->input('status') === 'failed') {
            $query->whereHas('analysis', fn ($analysis) => $analysis->where('status', 'failed'));
        } elseif ($request->input('status') === 'pending') {
            $query->whereDoesntHave('analysis', fn ($analysis) => $analysis->whereIn('status', ['completed', 'failed']));
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('admin.analyses.index', compact('applications'));
    }

    public function run(Application $application, ResumeAnalysisService $service)
    {
        $analysis = $service->analyze($application);
        AdminActivityLogger::log('analysis.executed', $application, ['status' => $analysis->status, 'score' => $analysis->match_score]);

        if ($analysis->status === 'failed') {
            return back()->withErrors(['analysis' => $analysis->error_message]);
        }

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Análise do currículo concluída.');
    }
}
