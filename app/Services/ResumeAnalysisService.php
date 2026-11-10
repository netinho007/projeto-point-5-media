<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationAnalysis;
use App\Models\Skill;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ResumeAnalysisService
{
    private const SKILLS = [
        'PHP', 'Laravel', 'MySQL', 'SQL', 'PostgreSQL', 'JavaScript', 'TypeScript',
        'HTML', 'CSS', 'React', 'Vue', 'Angular', 'Node.js', 'Python', 'Java', 'C#',
        '.NET', 'C++', 'Git', 'GitHub', 'GitLab', 'Docker', 'Kubernetes', 'AWS',
        'Azure', 'Google Cloud', 'Linux', 'Windows Server', 'REST', 'API', 'GraphQL',
        'Redis', 'MongoDB', 'Oracle', 'Excel', 'Power BI', 'Tableau', 'Figma',
        'Scrum', 'Kanban', 'Agile', 'DevOps', 'CI/CD', 'Terraform', 'Ansible',
        'Inglês', 'Espanhol', 'Atendimento', 'Vendas', 'Liderança', 'Gestão de Projetos',
    ];

    public function __construct(private ResumeTextExtractor $extractor)
    {
    }

    public function analyze(Application $application): ApplicationAnalysis
    {
        $application->loadMissing(['candidate', 'vacancy', 'analysis']);

        $analysis = $application->analysis()->firstOrNew();
        $analysis->fill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        try {
            $path = Storage::disk('local')->path($application->resume_path);
            $text = $this->extractor->extract($path, $application->resume_mime);
            $professionalProfile = $this->professionalProfileText($application);
            $analysisText = trim($text."\n".$professionalProfile);

            if (mb_strlen(trim($analysisText)) < 40) {
                throw new \RuntimeException('O currículo possui pouco texto legível para realizar a análise.');
            }

            $skills = $this->detectSkills($analysisText);
            $requirements = $this->requirements($application->vacancy?->requirements ?? '');
            [$matched, $missing] = $this->matchRequirements($requirements, $analysisText, $skills);
            $score = count($requirements) > 0
                ? round((count($matched) / count($requirements)) * 100, 2)
                : null;

            $experience = $this->extractExperience($analysisText, $skills);
            $education = $this->extractEducation($analysisText);
            $languages = $application->candidate?->languages?->map(fn ($item) => ['idioma' => $item->language, 'nivel' => $item->level])->values()->all() ?? [];
            $certifications = $application->candidate?->certifications?->map(fn ($item) => ['nome' => $item->name, 'instituicao' => $item->issuer])->values()->all() ?? [];
            $profileHighlights = array_values(array_filter([
                filled($application->candidate?->current_role) ? 'Cargo atual: '.$application->candidate->current_role : null,
                filled($application->candidate?->desired_role) ? 'Objetivo: '.$application->candidate->desired_role : null,
                filled($application->candidate?->years_experience) ? 'Experiência declarada: '.$application->candidate->years_experience.' ano(s)' : null,
                $application->candidate?->educations?->isNotEmpty() ? 'Formações estruturadas: '.$application->candidate->educations->count() : null,
                $application->candidate?->projects?->isNotEmpty() ? 'Projetos cadastrados: '.$application->candidate->projects->count() : null,
            ]));
            $summary = $this->buildSummary($skills, $matched, $missing, $score);

            $analysis->fill([
                'status' => 'completed',
                'match_score' => $score,
                'summary' => $summary,
                'extracted_text' => $text,
                'skills' => $skills,
                'experience' => $experience,
                'education' => $education,
                'matched_requirements' => $matched,
                'missing_requirements' => $missing,
                'languages' => $languages,
                'certifications' => $certifications,
                'profile_highlights' => $profileHighlights,
                'error_message' => null,
                'analyzed_at' => now(),
            ])->save();

            // A análise automática não altera a etapa pública do processo.
            // O status da candidatura é sempre definido pelo recrutador.
            $application->forceFill([
                'analyzed_at' => now(),
            ])->save();

            $this->syncCandidateSkills($application, $skills);

            return $analysis->fresh();
        } catch (Throwable $exception) {
            // Remove atributos pendentes de uma tentativa de save que tenha falhado
            // (ex.: texto de PDF com codificação inválida).
            if ($analysis->exists) {
                $analysis->refresh();
            }

            $previous = mb_substitute_character();
            mb_substitute_character('none');
            $safeError = mb_convert_encoding($exception->getMessage(), 'UTF-8', 'UTF-8');
            if ($previous !== false) {
                mb_substitute_character($previous);
            }

            $analysis->fill([
                'status' => 'failed',
                'error_message' => mb_substr($safeError, 0, 5000),
                'analyzed_at' => now(),
            ])->save();

            return $analysis->fresh();
        }
    }

    private function detectSkills(string $text): array
    {
        $normalized = $this->normalize($text);
        $found = [];

        foreach (self::SKILLS as $skill) {
            $needle = $this->normalize($skill);
            $pattern = '/(?<![a-z0-9])'.preg_quote($needle, '/').'(?![a-z0-9])/u';

            if (preg_match($pattern, $normalized)) {
                $found[] = $skill;
            }
        }

        return array_values(array_unique($found));
    }

    private function requirements(string $requirements): array
    {
        if (trim($requirements) === '') {
            return [];
        }

        $lines = preg_split('/\R|;|,|•|·/u', $requirements) ?: [];
        $result = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/^[\-–—*\d.()\s]+/u', '', $line) ?? $line);

            if ($line === '' || $this->containsSensitiveRequirement($line)) {
                continue;
            }

            $result[] = $line;
        }

        return array_values(array_unique(array_slice($result, 0, 30)));
    }

    private function containsSensitiveRequirement(string $requirement): bool
    {
        $normalized = $this->normalize($requirement);
        $blockedTerms = [
            'idade', 'anos de idade', 'genero', 'sexo', 'estado civil', 'casado', 'solteiro',
            'religiao', 'raca', 'etnia', 'cor da pele', 'nacionalidade', 'orientacao sexual',
            'identidade de genero', 'aparencia', 'foto', 'deficiencia', 'gestante', 'gravidez',
        ];

        foreach ($blockedTerms as $term) {
            $needle = preg_quote($this->normalize($term), '/');
            if (preg_match('/(?<![a-z0-9])'.$needle.'(?![a-z0-9])/u', $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function matchRequirements(array $requirements, string $text, array $skills): array
    {
        $normalizedText = $this->normalize($text);
        $normalizedSkills = array_map(fn (string $skill) => $this->normalize($skill), $skills);
        $matched = [];
        $missing = [];

        foreach ($requirements as $requirement) {
            $normalizedRequirement = $this->normalize($requirement);
            $tokens = array_values(array_filter(
                preg_split('/[^a-z0-9+#.]+/u', $normalizedRequirement) ?: [],
                fn (string $token) => mb_strlen($token) >= 2 && ! in_array($token, [
                    'com', 'para', 'em', 'de', 'da', 'do', 'das', 'dos', 'experiencia',
                    'conhecimento', 'conhecimentos', 'desejavel', 'obrigatorio', 'nivel',
                ], true)
            ));

            $direct = $normalizedRequirement !== '' && str_contains($normalizedText, $normalizedRequirement);
            $skillHit = collect($normalizedSkills)->contains(
                fn (string $skill) => str_contains($normalizedRequirement, $skill)
            );

            $tokenHits = count($tokens) === 0
                ? 0
                : count(array_filter($tokens, fn (string $token) => str_contains($normalizedText, $token)));

            $ratio = count($tokens) > 0 ? $tokenHits / count($tokens) : 0;

            if ($direct || $skillHit || $ratio >= 0.6) {
                $matched[] = $requirement;
            } else {
                $missing[] = $requirement;
            }
        }

        return [$matched, $missing];
    }

    private function extractExperience(string $text, array $skills): array
    {
        $experience = [];

        foreach ($skills as $skill) {
            $quoted = preg_quote($skill, '/');
            $patterns = [
                '/(\d{1,2})\s*(?:anos?|years?).{0,35}'. $quoted .'/iu',
                '/'. $quoted .'.{0,35}(\d{1,2})\s*(?:anos?|years?)/iu',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $text, $match)) {
                    $experience[$skill] = (int) $match[1].' ano(s)';
                    break;
                }
            }
        }

        return $experience;
    }

    private function extractEducation(string $text): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $education = [];

        foreach ($lines as $line) {
            $clean = trim($line);
            $normalized = $this->normalize($clean);

            if ($clean === '' || mb_strlen($clean) > 180) {
                continue;
            }

            foreach ([
                'graduacao', 'bacharel', 'tecnologo', 'tecnologia em', 'licenciatura',
                'pos-graduacao', 'pos graduacao', 'mba', 'mestrado', 'doutorado',
                'ensino superior', 'formacao academica',
            ] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $education[] = $clean;
                    break;
                }
            }
        }

        return array_values(array_unique(array_slice($education, 0, 8)));
    }

    private function buildSummary(array $skills, array $matched, array $missing, ?float $score): string
    {
        $parts = [];
        $parts[] = count($skills).' competência(s) técnica(s)/profissional(is) identificada(s).';

        if ($score !== null) {
            $parts[] = 'Aderência textual aos requisitos cadastrados: '.number_format($score, 0, ',', '.').'%.'.
                ' Foram encontrados '.count($matched).' de '.(count($matched) + count($missing)).' requisitos.';
        } else {
            $parts[] = 'A vaga não possui requisitos estruturados suficientes para calcular aderência.';
        }

        $parts[] = 'Use esta análise como apoio à revisão humana; ela não deve decidir automaticamente a contratação.';

        return implode(' ', $parts);
    }

    private function syncCandidateSkills(Application $application, array $skills): void
    {
        if (! $application->candidate) {
            return;
        }

        $ids = [];

        foreach ($skills as $skillName) {
            $skill = Skill::firstOrCreate(
                ['slug' => Str::slug($skillName)],
                ['name' => $skillName]
            );

            $ids[$skill->id] = [
                'source' => 'resume',
                'confidence' => 100,
            ];
        }

        $application->candidate->skills()->syncWithoutDetaching($ids);
    }


    private function professionalProfileText(Application $application): string
    {
        $candidate = $application->candidate;
        if (! $candidate) return '';

        $candidate->loadMissing(['skills','languages','certifications','courses','experiences','educations','projects']);
        $parts = [];
        if (filled($candidate->headline)) $parts[] = 'Título profissional: '.$candidate->headline;
        if (filled($candidate->current_role)) $parts[] = 'Cargo atual: '.$candidate->current_role;
        if (filled($candidate->desired_role)) $parts[] = 'Cargo desejado: '.$candidate->desired_role;
        if (filled($candidate->preferred_area)) $parts[] = 'Área de interesse: '.$candidate->preferred_area;
        if ($candidate->skills->isNotEmpty()) $parts[] = 'Competências: '.$candidate->skills->pluck('name')->implode(', ');
        if ($candidate->languages->isNotEmpty()) $parts[] = 'Idiomas: '.$candidate->languages->map(fn ($l) => $l->language.' '.$l->level)->implode(', ');
        if ($candidate->certifications->isNotEmpty()) $parts[] = 'Certificações: '.$candidate->certifications->map(fn ($c) => trim($c->name.' '.$c->issuer))->implode(', ');
        if ($candidate->courses->isNotEmpty()) $parts[] = 'Cursos: '.$candidate->courses->map(fn ($c) => trim($c->name.' '.$c->institution))->implode(', ');
        foreach ($candidate->experiences as $experience) {
            $parts[] = trim('Experiência: '.$experience->role.' '.$experience->company.' '.$experience->description);
        }
        foreach ($candidate->educations as $education) {
            $parts[] = trim('Formação: '.$education->course.' '.$education->institution.' '.$education->degree);
        }
        foreach ($candidate->projects as $project) {
            $parts[] = trim('Projeto: '.$project->name.' '.$project->technologies.' '.$project->description);
        }

        // Intencionalmente não inclui foto, telefone, nome, idade, gênero, endereço ou qualquer característica pessoal.
        return implode("\n", $parts);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(Str::ascii($value));
    }
}
