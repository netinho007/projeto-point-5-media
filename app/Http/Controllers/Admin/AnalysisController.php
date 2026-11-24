<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AdminActivityLogger;
use App\Services\LocalResumeAnalyzer;
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
        } elseif ($request->input('status') === 'processing') {
            $query->whereHas('analysis', fn ($analysis) => $analysis->where('status', 'processing'));
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        return view('admin.analyses.index', compact('applications'));
    }

    public function run(Application $application, ResumeAnalysisService $service)
    {
        $scheduled = $service->schedule($application);
        if ($scheduled) {
            AdminActivityLogger::log('analysis.scheduled', $application);
        }

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', $scheduled ? 'Currículo enviado para análise local.' : 'Esta análise já está na fila ou em processamento.');
    }

    public function status(Application $application)
    {
        $analysis = $application->analysis;

        return response()->json(['status' => $analysis?->status ?? 'pending', 'run_id' => $analysis?->run_id])->header('Cache-Control', 'no-store');
    }

    public function engine(LocalResumeAnalyzer $ai)
    {
        return response()->json($ai->health())->header('Cache-Control', 'no-store');
    }
}
