<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CandidateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    public function index(Request $request)
    {
        $candidate = $request->user()->candidate;
        $candidate->load([
            'applications' => fn ($query) => $query->with(['vacancy', 'interview'])->latest(),
        ]);

        $notifications = $candidate->notifications()->limit(6)->get();
        $unreadNotifications = $candidate->notifications()->whereNull('read_at')->count();
        $upcomingInterview = $candidate->applications
            ->pluck('interview')
            ->filter()
            ->filter(fn ($interview) => $interview->scheduled_at?->isFuture())
            ->sortBy('scheduled_at')
            ->first();

        $profileChecklist = [
            'Foto de perfil' => filled($candidate->profile_photo_path),
            'Currículo principal' => filled($candidate->resume_path),
            'Experiência profissional' => $candidate->experiences()->exists(),
            'Formação acadêmica' => $candidate->educations()->exists(),
            'Idiomas' => $candidate->languages()->exists(),
            'Competências' => $candidate->skills()->exists(),
            'Cursos ou certificações' => $candidate->courses()->exists() || $candidate->certifications()->exists(),
            'E-mail verificado' => $request->user()->hasVerifiedEmail(),
            'Telefone verificado' => filled($candidate->phone_verified_at),
        ];

        return view('candidate.portal.index', compact(
            'candidate',
            'notifications',
            'unreadNotifications',
            'upcomingInterview',
            'profileChecklist'
        ));
    }

    public function profile(Request $request)
    {
        $candidate = $request->user()->candidate;
        $candidate->load(['languages','certifications','courses','experiences','educations','projects','skills','documents']);
        return view('candidate.portal.profile', compact('candidate'));
    }

    public function updateProfile(Request $request)
    {
        $candidate = $request->user()->candidate;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'headline' => ['nullable', 'string', 'max:160'],
            'preferred_area' => ['nullable', 'string', 'max:120'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'behance_url' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'current_role' => ['nullable','string','max:160'],
            'desired_role' => ['nullable','string','max:160'],
            'years_experience' => ['nullable','integer','min:0','max:60'],
            'availability' => ['nullable','string','max:80'],
            'work_mode' => ['nullable','in:presencial,hibrido,remoto,flexivel'],
            'salary_expectation' => ['nullable','numeric','min:0','max:99999999'],
            'contract_preference' => ['nullable','string','max:80'],
            'travel_availability' => ['nullable','boolean'],
            'relocation_availability' => ['nullable','boolean'],
            'photo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'resume' => ['nullable', 'file', 'mimes:pdf,docx', 'max:5120'],
            'talent_pool' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('photo')) {
            if ($candidate->profile_photo_path) Storage::disk('local')->delete($candidate->profile_photo_path);
            $validated['profile_photo_path'] = $request->file('photo')->store('candidatos/'.$candidate->id.'/foto', 'local');
        }
        unset($validated['photo']);

        if ($request->hasFile('resume')) {
            if ($candidate->resume_path) {
                Storage::disk('local')->delete($candidate->resume_path);
            }

            $file = $request->file('resume');
            $validated['resume_path'] = $file->store('curriculos/perfis/candidato-'.$candidate->id, 'local');
            $validated['resume_original_name'] = $file->getClientOriginalName();
            $validated['resume_mime'] = $file->getMimeType();
            $validated['resume_size'] = $file->getSize();
        }

        $validated['state'] = filled($validated['state'] ?? null) ? mb_strtoupper($validated['state']) : null;

        if (($validated['phone'] ?? null) !== $candidate->phone) {
            $validated['phone_verified_at'] = null;
            $validated['phone_verification_hash'] = null;
            $validated['phone_verification_expires_at'] = null;
            $validated['phone_verification_attempts'] = 0;
        }

        $validated['travel_availability'] = $request->boolean('travel_availability');
        $validated['relocation_availability'] = $request->boolean('relocation_availability');
        $validated['talent_pool'] = $request->boolean('talent_pool');
        $validated['talent_pool_consent_at'] = $request->boolean('talent_pool')
            ? ($candidate->talent_pool_consent_at ?: now())
            : null;
        $validated['talent_pool_expires_at'] = $request->boolean('talent_pool')
            ? ($candidate->talent_pool_expires_at?->isFuture() ? $candidate->talent_pool_expires_at : now()->addMonths(12))
            : null;

        $candidate->fill($validated);
        $candidate->profile_completed_at = $candidate->profile_completion >= 75 ? now() : $candidate->profile_completed_at;
        $candidate->save();

        $request->user()->update(['name' => $validated['name']]);

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function removePhoto(Request $request)
    {
        $candidate = $request->user()->candidate;
        if ($candidate->profile_photo_path) Storage::disk('local')->delete($candidate->profile_photo_path);
        $candidate->update(['profile_photo_path' => null]);
        return back()->with('success', 'Foto removida.');
    }

    public function resume(Request $request)
    {
        $candidate = $request->user()->candidate;
        abort_unless($candidate->resume_path && Storage::disk('local')->exists($candidate->resume_path), 404);

        $path = Storage::disk('local')->path($candidate->resume_path);
        $extension = strtolower(pathinfo($candidate->resume_path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return response()->file($path, [
                'Content-Type' => $candidate->resume_mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.($candidate->resume_original_name ?: 'curriculo.pdf').'"',
            ]);
        }

        return Storage::disk('local')->download($candidate->resume_path, $candidate->resume_original_name ?: 'curriculo.docx');
    }

    public function notifications(Request $request)
    {
        $candidate = $request->user()->candidate;
        $notifications = $candidate->notifications()->paginate(15);

        return view('candidate.portal.notifications', compact('candidate', 'notifications'));
    }

    public function markNotificationRead(Request $request, CandidateNotification $notification)
    {
        abort_unless($notification->candidate_id === $request->user()->candidate->id, 403);
        $notification->update(['read_at' => now()]);

        if ($notification->application_id) {
            return redirect()->route('candidate.applications.show', $notification->application_id);
        }

        return back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->candidate->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Notificações marcadas como lidas.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Seu e-mail já está verificado.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Novo link de verificação enviado.');
    }
}
