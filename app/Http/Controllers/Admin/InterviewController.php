<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateNotification;
use App\Services\AdminActivityLogger;
use App\Notifications\InterviewScheduledNotification;
use App\Notifications\InterviewCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InterviewController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Interview::query()->with(['application.candidate', 'application.vacancy']);

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->whereHas('application', function ($application) use ($term) {
                $application->whereHas('candidate', fn ($candidate) => $candidate->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('vacancy', fn ($vacancy) => $vacancy->where('title', 'like', "%{$term}%"));
            });
        }

        if ($request->input('period') === 'upcoming') {
            $query->where('scheduled_at', '>=', now());
        } elseif ($request->input('period') === 'past') {
            $query->where('scheduled_at', '<', now());
        }

        $interviews = $query->orderBy('scheduled_at')->paginate(20)->withQueryString();

        return view('admin.interviews.index', compact('interviews'));
    }

    public function store(Request $request, Application $application)
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'format' => ['required', 'in:online,presencial,telefone'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'candidate_instructions' => ['nullable', 'string', 'max:1500'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $existingInterview = $application->interview;
        $scheduleChanged = $existingInterview
            && ($existingInterview->scheduled_at?->format('Y-m-d H:i:s') !== Carbon::parse($validated['scheduled_at'])->format('Y-m-d H:i:s')
                || $existingInterview->format !== $validated['format']);

        $validated['created_by'] = auth()->id();
        if ($scheduleChanged) {
            $validated['candidate_response'] = null;
            $validated['candidate_response_at'] = null;
        }

        $interview = $application->interview()->updateOrCreate([], $validated);

        if ($application->status !== 'interview') {
            $application->update(['status' => 'interview']);
            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'status' => 'interview',
                'public_note' => 'Entrevista agendada. Confira os detalhes no seu portal.',
                'changed_by' => auth()->id(),
            ]);
        }

        CandidateNotification::create([
            'candidate_id' => $application->candidate_id,
            'application_id' => $application->id,
            'type' => 'interview',
            'title' => $existingInterview ? 'Entrevista atualizada' : 'Entrevista agendada',
            'message' => ($existingInterview ? 'Os dados da sua entrevista foram atualizados para ' : 'Uma entrevista foi agendada para ').$interview->scheduled_at->format('d/m/Y \à\s H:i').'.',
        ]);

        AdminActivityLogger::log('interview.scheduled', $application, [
            'scheduled_at' => $interview->scheduled_at->toIso8601String(),
            'format' => $interview->format,
        ]);

        try {
            $candidate = $application->candidate;
            if ($candidate?->user && (($candidate->contact_preferences['email'] ?? true) !== false)) {
                $candidate->user->notify(new InterviewScheduledNotification($application->fresh(['vacancy','interview'])));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Entrevista salva. O candidato foi notificado no portal e, se configurado, por e-mail.');
    }

    public function destroy(Application $application)
    {
        if ($application->interview) {
            $application->interview()->delete();

            if ($application->status === 'interview') {
                $application->update(['status' => 'shortlisted']);
                ApplicationStatusHistory::create([
                    'application_id' => $application->id,
                    'status' => 'shortlisted',
                    'public_note' => 'A entrevista foi cancelada ou será reorganizada. O recrutamento entrará em contato quando houver uma nova definição.',
                    'changed_by' => auth()->id(),
                ]);
            }

            CandidateNotification::create([
                'candidate_id' => $application->candidate_id,
                'application_id' => $application->id,
                'type' => 'interview',
                'title' => 'Atualização da entrevista',
                'message' => 'A entrevista foi cancelada ou será reorganizada. Acompanhe o portal para novas informações.',
            ]);

            try {
                $candidate = $application->candidate;
                if ($candidate?->user && (($candidate->contact_preferences['email'] ?? true) !== false)) {
                    $candidate->user->notify(new InterviewCancelledNotification($application->fresh('vacancy')));
                }
            } catch (\Throwable $exception) {
                report($exception);
            }

            AdminActivityLogger::log('interview.deleted', $application);
        }

        return back()->with('success', 'Entrevista removida e candidato notificado.');
    }
}
