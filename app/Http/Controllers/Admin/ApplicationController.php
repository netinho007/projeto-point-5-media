<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateNotification;
use App\Models\Vacancy;
use App\Services\AdminActivityLogger;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::query()->with(['candidate', 'vacancy', 'analysis', 'interview']);

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($term) {
                $sub->whereHas('candidate', function ($candidate) use ($term) {
                    $candidate->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('vacancy', fn ($vacancy) => $vacancy->where('title', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', $request->integer('vacancy_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('analysis') === 'pending') {
            $query->whereDoesntHave('analysis', fn ($analysis) => $analysis->where('status', 'completed'));
        } elseif ($request->input('analysis') === 'completed') {
            $query->whereHas('analysis', fn ($analysis) => $analysis->where('status', 'completed'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->latest()->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title')->get(['id', 'title']);

        return view('admin.applications.index', compact('applications', 'vacancies'));
    }

    public function show(Application $application)
    {
        $application->load([
            'candidate.skills','candidate.languages','candidate.certifications','candidate.experiences','candidate.tags',
            'vacancy',
            'analysis',
            'statusHistories.changedBy',
            'interview',
            'notes.user',
        ]);

        return view('admin.applications.show', compact('application'));
    }

    public function updateStatus(Request $request, Application $application)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Application::STATUSES))],
            'public_note' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $application->status;
        $application->update(['status' => $validated['status']]);

        if ($previous !== $validated['status'] || filled($validated['public_note'] ?? null)) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'status' => $validated['status'],
                'public_note' => $validated['public_note'] ?? Application::STATUS_DESCRIPTIONS[$validated['status']] ?? null,
                'changed_by' => auth()->id(),
            ]);

            CandidateNotification::create([
                'candidate_id' => $application->candidate_id,
                'application_id' => $application->id,
                'type' => 'status',
                'title' => 'Atualização no seu processo',
                'message' => 'Sua candidatura para '.($application->vacancy?->title ?? 'a vaga').' agora está em: '.$application->status_label.'.',
            ]);

            try {
                $candidate = $application->candidate;
                if ($candidate?->user && (($candidate->contact_preferences['email'] ?? true) !== false)) {
                    $candidate->user->notify(new ApplicationStatusChangedNotification($application->fresh('vacancy'), $validated['public_note'] ?? null));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        AdminActivityLogger::log('application.status_updated', $application, [
            'from' => $previous,
            'to' => $validated['status'],
        ]);

        return back()->with('success', 'Status da candidatura atualizado. O candidato verá a nova etapa no portal.');
    }

    public function resume(Application $application)
    {
        abort_unless(Storage::disk('local')->exists($application->resume_path), 404, 'Currículo não encontrado.');

        $path = Storage::disk('local')->path($application->resume_path);
        $extension = strtolower(pathinfo($application->resume_path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return response()->file($path, [
                'Content-Type' => $application->resume_mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.($application->resume_original_name ?: 'curriculo.pdf').'"',
            ]);
        }

        return Storage::disk('local')->download(
            $application->resume_path,
            $application->resume_original_name ?: basename($application->resume_path)
        );
    }

    public function downloadResume(Application $application)
    {
        abort_unless(Storage::disk('local')->exists($application->resume_path), 404, 'Currículo não encontrado.');

        return Storage::disk('local')->download(
            $application->resume_path,
            $application->resume_original_name ?: basename($application->resume_path)
        );
    }
}
