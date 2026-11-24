<?php

namespace App\Services;

use App\Jobs\AnalyzeResume;
use App\Models\Application;
use App\Models\ApplicationAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ResumeAnalysisService
{
    public function __construct(private ResumeTextExtractor $extractor, private LocalResumeAnalyzer $ai) {}

    public function schedule(Application $application): bool
    {
        return DB::transaction(function () use ($application) {
            Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $analysis = $application->analysis()->firstOrNew();
            if (in_array($analysis->status, ['pending', 'processing'], true)
                && $analysis->run_id && $analysis->updated_at->gt(now()->subMinutes(15))) {
                return false;
            }

            $runId = (string) Str::uuid();
            $analysis->fill([
                'status' => 'pending', 'run_id' => $runId, 'error_message' => null,
                'started_at' => null, 'analyzed_at' => null,
            ])->save();
            AnalyzeResume::dispatch($application->id, $runId)->onConnection('analysis')->onQueue('resumes')->afterCommit();

            return true;
        });
    }

    public function analyze(Application $application, ?string $runId = null): ApplicationAnalysis
    {
        $application->loadMissing(['candidate', 'vacancy']);
        $analysis = $application->analysis()->firstOrCreate([], ['status' => 'pending']);
        if ($runId) {
            $claimed = ApplicationAnalysis::whereKey($analysis->id)->where('run_id', $runId)->where('status', 'pending')
                ->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
            if (! $claimed) {
                return $analysis->fresh();
            }
        } else {
            $analysis->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
        }
        $started = microtime(true);

        try {
            $text = $this->extractor->extract(Storage::disk('local')->path($application->resume_path), $application->resume_mime);
            if (mb_strlen($text) < 40) {
                throw new RuntimeException('O arquivo não tem texto legível suficiente. Envie um PDF com texto selecionável ou um DOCX; PDFs escaneados precisam de OCR.');
            }

            $source = $this->professionalText($application, $text);
            $limit = (int) config('resume_ai.max_characters');
            $limitations = ['Informações não identificadas não significam ausência de competência. Confirme as evidências na revisão humana.'];
            if (mb_strlen($source) > $limit) {
                $source = mb_substr($source, 0, $limit);
                $limitations[] = 'Documento longo: somente os primeiros '.$limit.' caracteres foram analisados.';
            }
            $requirements = $this->requirements($application->vacancy?->requirements ?? '');
            $rawCount = count(array_filter(preg_split('/\R|;/u', $application->vacancy?->requirements ?? '') ?: []));
            if ($rawCount > count($requirements)) {
                $limitations[] = 'Requisitos pessoais foram excluídos ou a vaga excedeu o limite de 20 requisitos profissionais.';
            }
            $result = $this->ai->analyze($source, $requirements, $application->vacancy?->title ?? '');
            $evidence = collect($result['requirements']);
            $score = $evidence->isEmpty() ? null : round($evidence->sum(fn ($item) => match ($item['status']) {
                'met' => 1, 'partial' => 0.5, default => 0,
            }) / $evidence->count() * 100, 2);

            $values = [
                'status' => 'completed', 'provider' => 'ollama', 'model' => config('resume_ai.model'),
                'match_score' => $score, 'summary' => $result['summary'], 'extracted_text' => $text,
                'skills' => $result['skills'], 'experience' => $result['experience'], 'education' => $result['education'],
                'matched_requirements' => $evidence->where('status', 'met')->pluck('requirement')->values()->all(),
                'missing_requirements' => $evidence->where('status', '!=', 'met')->pluck('requirement')->values()->all(),
                'requirement_evidence' => $result['requirements'], 'interview_questions' => $result['questions'],
                'languages' => [], 'certifications' => [], 'profile_highlights' => [],
                'limitations' => $limitations, 'error_message' => null, 'analyzed_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ];
            $query = ApplicationAnalysis::whereKey($analysis->id);
            if ($runId) {
                $query->where('run_id', $runId);
            }
            DB::transaction(function () use ($query, $values, $application) {
                $current = $query->lockForUpdate()->first();
                if ($current) {
                    $current->fill($values)->save();
                    $application->update(['analyzed_at' => now()]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $query = ApplicationAnalysis::whereKey($analysis->id);
            if ($runId) {
                $query->where('run_id', $runId);
            }
            $query->update([
                'status' => 'failed', 'match_score' => null,
                'error_message' => $exception instanceof RuntimeException ? mb_substr($exception->getMessage(), 0, 1000)
                    : 'Não foi possível concluir a leitura. Confira o arquivo e tente novamente.',
                'analyzed_at' => now(),
            ]);
        }

        // Não altera o status público nem as competências autodeclaradas do candidato.
        return $analysis->fresh();
    }

    private function requirements(string $text): array
    {
        $lines = preg_split('/\R|;/u', $text) ?: [];
        $blocked = '/\b(idade|genero|sexo|estado civil|religiao|raca|etnia|nacionalidade|aparencia|foto|deficiencia|gravidez|gestante|orientacao sexual)\b/';
        $lines = array_filter(array_map(fn ($line) => trim(preg_replace('/^\s*(?:[\-\x{2022}*]\s*|\d+[.)]\s+)/u', '', $line)), $lines),
            fn ($line) => $line !== '' && ! preg_match($blocked, strtolower(Str::ascii($line))));

        return array_values(array_slice(array_unique($lines), 0, 20));
    }

    private function professionalText(Application $application, string $text): string
    {
        $candidate = $application->candidate;
        if ($candidate) {
            foreach ([$candidate->name, $candidate->email, $candidate->phone] as $personal) {
                if (filled($personal)) {
                    $text = str_ireplace($personal, '[contato omitido]', $text);
                }
            }
            $candidate->loadMissing(['skills', 'languages', 'certifications', 'courses', 'experiences', 'educations', 'projects']);
            $parts = array_filter([$candidate->headline, $candidate->current_role, $candidate->desired_role]);
            $parts[] = 'Competências declaradas: '.$candidate->skills->filter(fn ($skill) => $skill->pivot?->source !== 'resume')->pluck('name')->implode(', ');
            foreach ($candidate->experiences as $item) {
                $parts[] = trim('Experiência: '.$item->role.' '.$item->company.' '.$item->description);
            }
            foreach ($candidate->educations as $item) {
                $parts[] = trim('Formação: '.$item->course.' '.$item->institution.' '.$item->degree);
            }
            foreach ($candidate->projects as $item) {
                $parts[] = trim('Projeto: '.$item->name.' '.$item->technologies.' '.$item->description);
            }
            foreach ($candidate->languages as $item) {
                $parts[] = 'Idioma declarado: '.$item->language.' '.$item->level;
            }
            foreach ($candidate->certifications as $item) {
                $parts[] = 'Certificação: '.$item->name.' '.$item->issuer;
            }
            foreach ($candidate->courses as $item) {
                $parts[] = 'Curso: '.$item->name.' '.$item->institution;
            }
            $text .= "\n\nPERFIL PROFISSIONAL DECLARADO\n".implode("\n", $parts);
        }
        $text = preg_replace('/[\w.+-]+@[\w.-]+\.[a-z]{2,}/iu', '[e-mail omitido]', $text);
        $text = preg_replace('/^\s*(?:idade|sexo|gênero|estado civil|religião|raça|nascimento|endereço|cpf|rg)\s*:.*$/imu', '[dado pessoal omitido]', $text);

        return trim($text);
    }
}
