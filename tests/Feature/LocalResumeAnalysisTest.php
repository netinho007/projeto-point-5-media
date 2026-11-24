<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeResume;
use App\Models\Application;
use App\Models\ApplicationAnalysis;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\LocalResumeAnalyzer;
use App\Services\ResumeAnalysisService;
use App\Services\ResumeTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalResumeAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['resume_ai.url' => 'http://127.0.0.1:11434', 'resume_ai.model' => 'qwen3:4b']);
        Storage::fake('local');
    }

    public function test_analysis_is_queued_once_and_never_runs_in_request(): void
    {
        Queue::fake();
        $application = $this->application();
        $service = app(ResumeAnalysisService::class);
        $this->assertTrue($service->schedule($application));
        $this->assertFalse($service->schedule($application));
        Queue::assertPushed(AnalyzeResume::class, fn ($job) => $job->connection === 'analysis' && $job->queue === 'resumes');
        Queue::assertPushed(AnalyzeResume::class, 1);
        Http::assertNothingSent();
        $this->assertSame('pending', $application->fresh('analysis')->analysis->status);
    }

    public function test_ai_evidence_score_privacy_and_human_decision_boundaries(): void
    {
        $application = $this->application();
        $this->fakeResume();
        $this->fakeAnswer();
        $analysis = app(ResumeAnalysisService::class)->analyze($application);
        $this->assertSame('completed', $analysis->status);
        $this->assertSame('ollama', $analysis->provider);
        $this->assertSame('50.00', $analysis->match_score);
        $this->assertSame(['PHP'], $analysis->skills);
        $this->assertSame('partial', $analysis->requirement_evidence[1]['status']);
        $this->assertSame('5 anos de experiência em Laravel', $analysis->requirement_evidence[1]['requirement']);
        $this->assertSame('not_found', $analysis->requirement_evidence[2]['status']);
        $this->assertSame('', $analysis->requirement_evidence[2]['evidence']);
        $this->assertSame('received', $application->fresh()->status);
        $this->assertSame(0, $application->candidate->skills()->count());
        Http::assertSent(function ($request) {
            $content = $request['messages'][1]['content'];

            return $request->url() === 'http://127.0.0.1:11434/api/chat' && $request['stream'] === false
                && ! str_contains($content, 'pessoa@example.com') && ! str_contains($content, 'Pessoa Teste')
                && ! str_contains($content, 'idade máxima') && ! str_contains($content, '99999');
        });
    }

    public function test_reanalysis_reuses_record_and_old_jobs_cannot_overwrite(): void
    {
        Queue::fake();
        $application = $this->application();
        $this->fakeResume();
        $this->fakeAnswer();
        $service = app(ResumeAnalysisService::class);
        $service->analyze($application);
        $service->analyze($application);
        $this->assertSame(1, ApplicationAnalysis::count());
        $service->schedule($application);
        $runId = $application->fresh('analysis')->analysis->run_id;
        (new AnalyzeResume($application->id, 'stale-run'))->handle($service);
        $this->assertSame('pending', $application->fresh('analysis')->analysis->status);
        (new AnalyzeResume($application->id, $runId))->handle($service);
        $this->assertSame('completed', $application->fresh('analysis')->analysis->status);
        Http::assertSentCount(3);
    }

    public function test_missing_or_scanned_resume_does_not_fallback_to_fake_ai(): void
    {
        $application = $this->application();
        $this->mock(ResumeTextExtractor::class)->shouldReceive('extract')->once()->andReturn('');
        $analysis = app(ResumeAnalysisService::class)->analyze($application);
        $this->assertSame('failed', $analysis->status);
        $this->assertNull($analysis->match_score);
        $this->assertStringContainsString('OCR', $analysis->error_message);
        Http::assertNothingSent();
    }

    public function test_ollama_failure_produces_recoverable_state(): void
    {
        $this->fakeResume();
        Http::fake(['*/api/chat' => Http::response(['error' => 'model not found'], 404)]);
        $analysis = app(ResumeAnalysisService::class)->analyze($this->application());
        $this->assertSame('failed', $analysis->status);
        $this->assertStringContainsString('instalado', $analysis->error_message);
    }

    public function test_invalid_model_output_is_not_accepted(): void
    {
        $this->fakeResume();
        Http::fake(['*/api/chat' => Http::response(['done' => true, 'message' => ['content' => '{broken']])]);
        $analysis = app(ResumeAnalysisService::class)->analyze($this->application());
        $this->assertSame('failed', $analysis->status);
        $this->assertNull($analysis->match_score);
    }

    public function test_cloud_endpoints_are_rejected_before_sending_curriculum(): void
    {
        config(['resume_ai.url' => 'https://external.example.com']);
        $this->expectException(\RuntimeException::class);
        app(LocalResumeAnalyzer::class)->analyze('professional data', [], 'PHP');
    }

    public function test_health_distinguishes_installed_and_missing_model(): void
    {
        Http::fake(['*/api/tags' => Http::response(['models' => [['name' => 'qwen3:4b']]])]);
        $this->assertTrue(app(LocalResumeAnalyzer::class)->health()['ready']);
        config(['resume_ai.model' => 'not-installed']);
        $this->assertFalse(app(LocalResumeAnalyzer::class)->health()['ready']);
    }

    public function test_ai_admin_routes_cannot_be_accessed_by_candidates(): void
    {
        $application = $this->application();
        $this->actingAs($application->candidate->user)->get(route('admin.analyses.status', $application))->assertForbidden();
        $this->get(route('admin.analyses.engine'))->assertForbidden();
        $this->post(route('admin.analyses.run', $application))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_main_views_and_admin_analysis_render(): void
    {
        Queue::fake();
        $application = $this->application();
        $this->fakeResume();
        $this->fakeAnswer();
        app(ResumeAnalysisService::class)->analyze($application);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $report = $this->get(route('admin.applications.show', $application))->assertOk()->assertSee('Requisitos e evidências')->assertSee('qwen3:4b');
        $this->capture('admin-report', $report->getContent());
        foreach (['admin.dashboard', 'admin.analyses.index', 'admin.vacancies.index', 'admin.candidates.index', 'admin.applications.index', 'admin.talent-pool.index', 'admin.analytics.index', 'admin.messages.index', 'admin.interviews.index', 'admin.activity-logs.index', 'admin.message-templates.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->post(route('admin.analyses.run', $application))->assertRedirect(route('admin.applications.show', $application));
        $this->get(route('admin.analyses.status', $application))->assertOk()->assertJsonPath('status', 'pending');
        $this->get(route('admin.analyses.engine'))->assertOk()->assertJsonPath('ready', true);
        $this->actingAs($application->candidate->user);
        foreach (['candidate.portal', 'candidate.profile', 'candidate.security', 'candidate.messages.index', 'candidate.saved-vacancies.index', 'candidate.notifications'] as $route) {
            $response = $this->get(route($route))->assertOk();
            if ($route === 'candidate.portal') {
                $this->capture('candidate-portal', $response->getContent());
            }
        }
        foreach (['vacancies.index', 'career', 'process', 'faq', 'privacy'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('vacancies.show', $application->vacancy))->assertOk();
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('POINT_CAPTURE_UI') !== '1') {
            return;
        }
        File::ensureDirectoryExists(storage_path('app/qa'));
        File::put(storage_path('app/qa/'.$name.'.html'), $html);
    }

    private function application(): Application
    {
        $user = User::factory()->create(['name' => 'Pessoa Teste', 'email' => 'pessoa@example.com']);
        $candidate = $user->candidate()->create(['name' => $user->name, 'email' => $user->email, 'phone' => '(11) 99999-9999', 'privacy_consent_at' => now()]);
        $vacancy = Vacancy::create([
            'title' => 'Desenvolvedor PHP', 'department' => 'Tecnologia', 'location' => 'Remoto',
            'description' => 'Desenvolvimento de aplicações web.', 'requirements' => "PHP\n5 anos de experiência em Laravel\nDocker\nidade máxima 30 anos",
            'employment_type' => 'CLT', 'status' => 'open', 'work_mode' => 'remoto',
        ]);

        return Application::create(['candidate_id' => $candidate->id, 'vacancy_id' => $vacancy->id,
            'resume_path' => 'curriculos/test.pdf', 'resume_mime' => 'application/pdf', 'status' => 'received', 'consent_at' => now()]);
    }

    private function fakeResume(): void
    {
        $this->mock(ResumeTextExtractor::class)->shouldReceive('extract')->andReturn("Pessoa Teste\npessoa@example.com\n(11) 99999-9999\nPHP e Laravel: 2 anos de experiência.\nTecnólogo em Sistemas.");
    }

    private function fakeAnswer(): void
    {
        $data = ['summary' => 'Experiência em PHP e Laravel, com formação em Sistemas.', 'skills' => [
            ['name' => 'PHP', 'evidence' => 'PHP e Laravel'], ['name' => 'Docker', 'evidence' => 'Docker avançado'],
        ], 'requirements' => [
            ['id' => 0, 'status' => 'met', 'evidence' => 'PHP e Laravel', 'reason' => 'PHP mencionado no currículo.'],
            ['id' => 1, 'status' => 'partial', 'evidence' => '2 anos de experiência.', 'reason' => 'Experiência inferior aos 5 anos solicitados.'],
            ['id' => 2, 'status' => 'met', 'evidence' => 'Docker avançado', 'reason' => 'Texto inventado deve ser descartado.'],
        ], 'education' => ['Tecnólogo em Sistemas.'], 'experience' => ['PHP e Laravel: 2 anos de experiência.'], 'questions' => ['Como você organiza testes de aplicações PHP?']];
        Http::fake([
            '*/api/chat' => Http::response(['done' => true, 'message' => ['content' => json_encode($data)]]),
            '*/api/tags' => Http::response(['models' => [['name' => 'qwen3:4b']]]),
        ]);
    }
}
