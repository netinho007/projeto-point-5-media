<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\ApplicationAnalysis;
use App\Services\ResumeAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyzeResume implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $applicationId, public string $runId) {}

    public function handle(ResumeAnalysisService $service): void
    {
        $application = Application::find($this->applicationId);
        if ($application) {
            $service->analyze($application, $this->runId);
        }
    }

    public function failed(?Throwable $exception): void
    {
        ApplicationAnalysis::where('application_id', $this->applicationId)->where('run_id', $this->runId)->update([
            'status' => 'failed', 'match_score' => null,
            'error_message' => 'O processamento foi interrompido. Confira o Ollama e execute a análise novamente.',
            'analyzed_at' => now(),
        ]);
    }
}
