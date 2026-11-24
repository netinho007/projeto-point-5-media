<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\ResumeAnalysisService;
use App\Services\ResumeTextExtractor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumeQueuePipelineTest extends TestCase
{
    use DatabaseMigrations;

    public function test_persistent_queue_consumes_serialized_resume_job(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $candidate = $user->candidate()->create(['name' => $user->name, 'email' => $user->email, 'privacy_consent_at' => now()]);
        $vacancy = Vacancy::create(['title' => 'PHP', 'description' => 'Aplicações web.', 'requirements' => 'PHP',
            'employment_type' => 'CLT', 'status' => 'open']);
        $application = Application::create(['candidate_id' => $candidate->id, 'vacancy_id' => $vacancy->id,
            'resume_path' => 'test.pdf', 'status' => 'received', 'consent_at' => now()]);
        $this->mock(ResumeTextExtractor::class)->shouldReceive('extract')->once()->andReturn('Desenvolveu aplicações web usando PHP e testes automatizados.');
        Http::fake(['*/api/chat' => Http::response(['done' => true, 'message' => ['content' => json_encode([
            'summary' => 'Desenvolvimento de aplicações PHP.',
            'skills' => [['name' => 'PHP', 'evidence' => 'usando PHP']],
            'requirements' => [['id' => 0, 'status' => 'met', 'evidence' => 'usando PHP', 'reason' => 'Experiência mencionada.']],
            'education' => [], 'experience' => [], 'questions' => [],
        ])]])]);

        $this->assertTrue(app(ResumeAnalysisService::class)->schedule($application));
        $this->assertDatabaseHas('jobs', ['queue' => 'resumes']);
        $this->assertSame('pending', $application->fresh('analysis')->analysis->status);
        $this->artisan('queue:work', ['connection' => 'analysis', '--queue' => 'resumes', '--once' => true, '--tries' => 1])->assertSuccessful();
        $this->assertDatabaseCount('jobs', 0);
        $analysis = $application->fresh('analysis')->analysis;
        $this->assertSame('completed', $analysis->status);
        $this->assertSame('100.00', $analysis->match_score);
        $this->assertSame('received', $application->status);
        Http::assertSentCount(1);
    }
}
