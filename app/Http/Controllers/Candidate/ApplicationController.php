<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\CandidateNotification;
use App\Models\Vacancy;
use App\Services\ResumeAnalysisService;
use App\Notifications\ApplicationReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function create(Request $request, Vacancy $vacancy)
    {
        abort_unless(Vacancy::open()->whereKey($vacancy->id)->exists(), 404);
        $candidate = $request->user()->candidate;
        $existing = $candidate->applications()->where('vacancy_id', $vacancy->id)->first();

        return view('candidate.applications.create', compact('candidate', 'vacancy', 'existing'));
    }

    public function store(Request $request, Vacancy $vacancy, ResumeAnalysisService $analysisService)
    {
        abort_unless(Vacancy::open()->whereKey($vacancy->id)->exists(), 404);
        $candidate = $request->user()->candidate;

        if ($candidate->applications()->where('vacancy_id', $vacancy->id)->exists()) {
            return redirect()->route('candidate.portal')->withErrors([
                'application' => 'Você já possui uma candidatura para esta vaga.',
            ]);
        }

        $rules = [
            'resume' => [$candidate->resume_path ? 'nullable' : 'required', 'file', 'mimes:pdf,docx', 'max:5120'],
            'use_profile_resume' => ['nullable', 'boolean'],
            'save_as_profile_resume' => ['nullable', 'boolean'],
            'cover_letter' => ['nullable','string','max:3000'],
            'referral_name' => ['nullable','string','max:255'],
            'source' => ['nullable','string','max:80'],
        ];
        $request->validate($rules, [
            'resume.required' => 'Adicione um currículo ao perfil ou envie um arquivo para esta candidatura.',
            'resume.mimes' => 'O currículo deve estar em PDF ou DOCX.',
        ]);

        if (! $request->hasFile('resume') && (! $candidate->resume_path || ! Storage::disk('local')->exists($candidate->resume_path))) {
            return back()->withErrors(['resume' => 'O currículo salvo no seu perfil não foi encontrado. Envie um novo arquivo.']);
        }

        $application = DB::transaction(function () use ($request, $candidate, $vacancy) {
            $resumePath = null;
            $originalName = null;
            $mime = null;
            $size = null;

            if ($request->hasFile('resume')) {
                $file = $request->file('resume');
                $resumePath = $file->store('curriculos/vaga-'.$vacancy->id.'/candidato-'.$candidate->id, 'local');
                $originalName = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $size = $file->getSize();

                if ($request->boolean('save_as_profile_resume')) {
                    if ($candidate->resume_path) {
                        Storage::disk('local')->delete($candidate->resume_path);
                    }

                    $profilePath = 'curriculos/perfis/candidato-'.$candidate->id.'/'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                    Storage::disk('local')->copy($resumePath, $profilePath);
                    $candidate->update([
                        'resume_path' => $profilePath,
                        'resume_original_name' => $originalName,
                        'resume_mime' => $mime,
                        'resume_size' => $size,
                    ]);
                }
            } else {
                $extension = pathinfo($candidate->resume_path, PATHINFO_EXTENSION);
                $resumePath = 'curriculos/vaga-'.$vacancy->id.'/candidato-'.$candidate->id.'/'.Str::uuid().'.'.$extension;
                Storage::disk('local')->copy($candidate->resume_path, $resumePath);
                $originalName = $candidate->resume_original_name ?: 'curriculo.'.$extension;
                $mime = $candidate->resume_mime;
                $size = $candidate->resume_size;
            }

            $application = Application::create([
                'candidate_id' => $candidate->id,
                'vacancy_id' => $vacancy->id,
                'resume_path' => $resumePath,
                'resume_original_name' => $originalName,
                'resume_mime' => $mime,
                'resume_size' => $size,
                'status' => 'received',
                'consent_at' => now(),
                'cover_letter' => $request->input('cover_letter'),
                'referral_name' => $vacancy->referral_enabled ? $request->input('referral_name') : null,
                'source' => $request->input('source') ?: 'site',
            ]);

            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'status' => 'received',
                'public_note' => 'Sua candidatura foi recebida com sucesso.',
                'changed_by' => null,
            ]);

            CandidateNotification::create([
                'candidate_id' => $candidate->id,
                'application_id' => $application->id,
                'type' => 'application',
                'title' => 'Candidatura recebida',
                'message' => 'Recebemos sua candidatura para '.$vacancy->title.'.',
            ]);

            return $application;
        });

        try {
            $analysisService->analyze($application->fresh(['candidate', 'vacancy']));
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            if ($candidate->user && (($candidate->contact_preferences['email'] ?? true) !== false)) {
                $candidate->user->notify(new ApplicationReceivedNotification($application->fresh('vacancy')));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('candidate.applications.show', $application)
            ->with('success', 'Candidatura enviada com sucesso! Você pode acompanhar cada etapa pelo seu portal.');
    }

    public function withdraw(Request $request, Application $application)
    {
        $candidate = $request->user()->candidate;
        abort_unless($application->candidate_id === $candidate->id, 403);
        if (in_array($application->status, ['approved','archived','not_selected','withdrawn'], true)) {
            return back()->withErrors(['application' => 'Esta candidatura não pode mais ser retirada.']);
        }
        $application->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);
        ApplicationStatusHistory::create([
            'application_id' => $application->id, 'status' => 'withdrawn',
            'public_note' => 'Você retirou sua candidatura deste processo.', 'changed_by' => null,
        ]);
        CandidateNotification::create([
            'candidate_id' => $candidate->id, 'application_id' => $application->id, 'type' => 'status',
            'title' => 'Candidatura retirada', 'message' => 'Sua candidatura foi retirada conforme sua solicitação.',
        ]);
        return redirect()->route('candidate.portal')->with('success', 'Sua candidatura foi retirada.');
    }

    public function show(Request $request, Application $application)
    {
        $candidate = $request->user()->candidate;
        abort_unless($application->candidate_id === $candidate->id, 403);

        $application->load(['vacancy', 'statusHistories', 'interview']);

        if ($application->statusHistories->isEmpty()) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'status' => $application->status,
                'public_note' => $application->status_description,
            ]);
            $application->load('statusHistories');
        }

        $candidate->notifications()
            ->where('application_id', $application->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('candidate.applications.show', compact('candidate', 'application'));
    }

    public function respondInterview(Request $request, Application $application)
    {
        $candidate = $request->user()->candidate;
        abort_unless($application->candidate_id === $candidate->id, 403);
        abort_unless($application->interview, 404);

        $data = $request->validate([
            'response' => ['required','in:confirmed,needs_reschedule,declined'],
        ]);

        $application->interview->update([
            'candidate_response' => $data['response'],
            'candidate_response_at' => now(),
        ]);

        $labels = [
            'confirmed' => 'Presença confirmada',
            'needs_reschedule' => 'Pedido de reagendamento enviado',
            'declined' => 'Candidato informou que não poderá participar',
        ];

        CandidateNotification::create([
            'candidate_id' => $candidate->id,
            'application_id' => $application->id,
            'type' => 'interview',
            'title' => 'Resposta da entrevista registrada',
            'message' => $labels[$data['response']],
            'read_at' => now(),
        ]);

        return back()->with('success', $labels[$data['response']].'.');
    }
}
