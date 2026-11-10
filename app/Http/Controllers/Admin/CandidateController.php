<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateCertification;
use App\Models\CandidateCourse;
use App\Models\Vacancy;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query()
            ->with(['applications.vacancy', 'skills', 'user'])
            ->withCount('applications');

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('skills', fn ($skills) => $skills->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('vacancy_id')) {
            $query->whereHas('applications', fn ($applications) => $applications->where('vacancy_id', $request->integer('vacancy_id')));
        }

        if ($request->filled('status')) {
            $query->whereHas('applications', fn ($applications) => $applications->where('status', $request->input('status')));
        }

        if ($request->input('privacy') === 'deletion_requested') {
            $query->whereNotNull('deletion_requested_at');
        } elseif ($request->input('privacy') === 'inactive') {
            $query->where('account_active', false);
        }

        $candidates = $query->latest()->paginate(15)->withQueryString();
        $vacancies = Vacancy::orderBy('title')->get(['id', 'title']);

        return view('admin.candidates.index', compact('candidates', 'vacancies'));
    }

    public function resume(Candidate $candidate)
    {
        abort_unless($candidate->resume_path && Storage::disk('local')->exists($candidate->resume_path), 404, 'Currículo do perfil não encontrado.');

        $path = Storage::disk('local')->path($candidate->resume_path);
        $extension = strtolower(pathinfo($candidate->resume_path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return response()->file($path, [
                'Content-Type' => $candidate->resume_mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.($candidate->resume_original_name ?: 'curriculo.pdf').'"',
            ]);
        }

        return Storage::disk('local')->download(
            $candidate->resume_path,
            $candidate->resume_original_name ?: basename($candidate->resume_path)
        );
    }


    public function document(Candidate $candidate, CandidateDocument $document)
    {
        abort_unless($document->candidate_id === $candidate->id, 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function certificationFile(Candidate $candidate, CandidateCertification $certification)
    {
        abort_unless($certification->candidate_id === $candidate->id, 403);
        abort_unless($certification->file_path && Storage::disk('local')->exists($certification->file_path), 404);
        return Storage::disk('local')->download($certification->file_path, $certification->name.'.'.pathinfo($certification->file_path, PATHINFO_EXTENSION));
    }

    public function courseFile(Candidate $candidate, CandidateCourse $course)
    {
        abort_unless($course->candidate_id === $candidate->id, 403);
        abort_unless($course->certificate_path && Storage::disk('local')->exists($course->certificate_path), 404);
        return Storage::disk('local')->download($course->certificate_path, $course->name.'.'.pathinfo($course->certificate_path, PATHINFO_EXTENSION));
    }

    public function reactivate(Candidate $candidate)
    {
        $candidate->update([
            'account_active' => true,
            'deletion_requested_at' => null,
        ]);

        AdminActivityLogger::log('candidate.reactivated', $candidate);

        return back()->with('success', 'Conta do candidato reativada.');
    }

    public function resolveDeletionRequest(Candidate $candidate)
    {
        $candidate->update(['deletion_requested_at' => null]);
        AdminActivityLogger::log('candidate.deletion_request_resolved', $candidate);

        return back()->with('success', 'Solicitação de exclusão marcada como resolvida.');
    }

    public function destroyPrivacyData(Request $request, Candidate $candidate)
    {
        $request->validate([
            'confirmation' => ['required', 'in:EXCLUIR'],
        ], [
            'confirmation.in' => 'Digite EXCLUIR para confirmar a exclusão definitiva.',
        ]);

        abort_unless($candidate->deletion_requested_at !== null, 422, 'O candidato não possui solicitação de exclusão registrada.');

        $candidate->load(['user', 'applications', 'documents', 'certifications', 'courses', 'messages']);
        $user = $candidate->user;

        $paths = collect([
            $candidate->profile_photo_path,
            $candidate->resume_path,
        ])
            ->merge($candidate->applications->pluck('resume_path'))
            ->merge($candidate->documents->pluck('file_path'))
            ->merge($candidate->certifications->pluck('file_path'))
            ->merge($candidate->courses->pluck('certificate_path'))
            ->merge($candidate->messages->pluck('attachment_path'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        AdminActivityLogger::log('candidate.privacy_data_deleted', $candidate, [
            'requested_at' => $candidate->deletion_requested_at?->toIso8601String(),
        ]);

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }

        $candidate->delete();
        $user?->delete();

        return redirect()->route('admin.candidates.index')->with('success', 'Dados do candidato excluídos definitivamente.');
    }

    public function show(Candidate $candidate)
    {
        $candidate->load([
            'skills','languages','certifications','courses','experiences','educations','projects','documents','tags',
            'user',
            'applications' => fn ($query) => $query->with(['vacancy', 'analysis'])->latest(),
        ]);

        return view('admin.candidates.show', compact('candidate'));
    }
}
