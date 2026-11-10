<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\CandidateCertification;
use App\Models\CandidateCourse;
use App\Models\CandidateDocument;
use App\Models\CandidateEducation;
use App\Models\CandidateProject;
use App\Models\CandidateExperience;
use App\Models\CandidateLanguage;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileSectionController extends Controller
{
    private function candidate(Request $request)
    {
        return $request->user()->candidate;
    }

    public function languageStore(Request $request)
    {
        $data = $request->validate(['language' => ['required','string','max:100'], 'level' => ['required','in:Básico,Intermediário,Avançado,Fluente,Nativo']]);
        $this->candidate($request)->languages()->create($data);
        return back()->with('success', 'Idioma adicionado.');
    }

    public function languageDestroy(Request $request, CandidateLanguage $language)
    {
        abort_unless($language->candidate_id === $this->candidate($request)->id, 403);
        $language->delete();
        return back()->with('success', 'Idioma removido.');
    }

    public function experienceStore(Request $request)
    {
        $data = $request->validate([
            'company' => ['required','string','max:255'], 'role' => ['required','string','max:255'],
            'start_date' => ['nullable','date'], 'end_date' => ['nullable','date'], 'current' => ['nullable','boolean'],
            'description' => ['nullable','string','max:2500'],
        ]);
        $data['current'] = $request->boolean('current');
        if ($data['current']) $data['end_date'] = null;
        $this->candidate($request)->experiences()->create($data);
        return back()->with('success', 'Experiência adicionada.');
    }

    public function experienceDestroy(Request $request, CandidateExperience $experience)
    {
        abort_unless($experience->candidate_id === $this->candidate($request)->id, 403);
        $experience->delete();
        return back()->with('success', 'Experiência removida.');
    }

    public function certificationStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'issuer' => ['nullable','string','max:255'],
            'issued_at' => ['nullable','date'], 'expires_at' => ['nullable','date'], 'credential_id' => ['nullable','string','max:255'],
            'credential_url' => ['nullable','url','max:500'], 'certificate_file' => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);
        unset($data['certificate_file']);
        if ($request->hasFile('certificate_file')) $data['file_path'] = $request->file('certificate_file')->store('candidatos/'.$this->candidate($request)->id.'/certificados', 'local');
        $this->candidate($request)->certifications()->create($data);
        return back()->with('success', 'Certificação adicionada.');
    }

    public function certificationDestroy(Request $request, CandidateCertification $certification)
    {
        abort_unless($certification->candidate_id === $this->candidate($request)->id, 403);
        if ($certification->file_path) Storage::disk('local')->delete($certification->file_path);
        $certification->delete();
        return back()->with('success', 'Certificação removida.');
    }

    public function courseStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'institution' => ['nullable','string','max:255'],
            'workload_hours' => ['nullable','integer','min:1','max:10000'], 'completed_at' => ['nullable','date'],
            'certificate_file' => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);
        unset($data['certificate_file']);
        if ($request->hasFile('certificate_file')) $data['certificate_path'] = $request->file('certificate_file')->store('candidatos/'.$this->candidate($request)->id.'/cursos', 'local');
        $this->candidate($request)->courses()->create($data);
        return back()->with('success', 'Curso adicionado.');
    }

    public function courseDestroy(Request $request, CandidateCourse $course)
    {
        abort_unless($course->candidate_id === $this->candidate($request)->id, 403);
        if ($course->certificate_path) Storage::disk('local')->delete($course->certificate_path);
        $course->delete();
        return back()->with('success', 'Curso removido.');
    }

    public function skillsUpdate(Request $request)
    {
        $data = $request->validate(['skills' => ['nullable','string','max:1500']]);
        $names = collect(preg_split('/[,;\n]+/', (string) ($data['skills'] ?? '')))->map(fn ($v) => trim($v))->filter()->unique()->take(40);
        $ids = [];
        foreach ($names as $name) {
            $slug = Str::slug($name);
            if ($slug === '') continue;
            $skill = Skill::firstOrCreate(['slug' => $slug], ['name' => $name]);
            $ids[$skill->id] = ['source' => 'profile', 'confidence' => 100];
        }
        $this->candidate($request)->skills()->sync($ids);
        return back()->with('success', 'Competências atualizadas.');
    }

    public function documentStore(Request $request)
    {
        $data = $request->validate([
            'type' => ['required','in:portfolio,diploma,certificate,cover_letter,other'],
            'title' => ['required','string','max:255'],
            'document' => ['required','file','mimes:pdf,doc,docx,jpg,jpeg,png,webp','max:8192'],
        ]);
        $candidate = $this->candidate($request);
        $file = $request->file('document');
        $candidate->documents()->create([
            'type' => $data['type'], 'title' => $data['title'],
            'file_path' => $file->store('candidatos/'.$candidate->id.'/documentos', 'local'),
            'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize(),
        ]);
        return back()->with('success', 'Documento enviado.');
    }

    public function documentDownload(Request $request, CandidateDocument $document)
    {
        abort_unless($document->candidate_id === $this->candidate($request)->id, 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);
        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function documentDestroy(Request $request, CandidateDocument $document)
    {
        abort_unless($document->candidate_id === $this->candidate($request)->id, 403);
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Documento removido.');
    }

    public function educationStore(Request $request)
    {
        $data = $request->validate([
            'institution' => ['required','string','max:255'],
            'course' => ['required','string','max:255'],
            'degree' => ['nullable','string','max:80'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'current' => ['nullable','boolean'],
        ]);
        $data['current'] = $request->boolean('current');
        if ($data['current']) $data['end_date'] = null;
        $this->candidate($request)->educations()->create($data);
        return back()->with('success', 'Formação acadêmica adicionada.');
    }

    public function educationDestroy(Request $request, CandidateEducation $education)
    {
        abort_unless($education->candidate_id === $this->candidate($request)->id, 403);
        $education->delete();
        return back()->with('success', 'Formação acadêmica removida.');
    }

    public function projectStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string','max:2500'],
            'technologies' => ['nullable','string','max:500'],
            'project_url' => ['nullable','url','max:500'],
            'repository_url' => ['nullable','url','max:500'],
        ]);
        $this->candidate($request)->projects()->create($data);
        return back()->with('success', 'Projeto adicionado ao seu perfil.');
    }

    public function projectDestroy(Request $request, CandidateProject $project)
    {
        abort_unless($project->candidate_id === $this->candidate($request)->id, 403);
        $project->delete();
        return back()->with('success', 'Projeto removido.');
    }

    public function certificationDownload(Request $request, CandidateCertification $certification)
    {
        abort_unless($certification->candidate_id === $this->candidate($request)->id, 403);
        abort_unless($certification->file_path && Storage::disk('local')->exists($certification->file_path), 404);
        return Storage::disk('local')->download($certification->file_path, $certification->name.'.'.pathinfo($certification->file_path, PATHINFO_EXTENSION));
    }

    public function courseDownload(Request $request, CandidateCourse $course)
    {
        abort_unless($course->candidate_id === $this->candidate($request)->id, 403);
        abort_unless($course->certificate_path && Storage::disk('local')->exists($course->certificate_path), 404);
        return Storage::disk('local')->download($course->certificate_path, $course->name.'.'.pathinfo($course->certificate_path, PATHINFO_EXTENSION));
    }

}
