<?php

namespace App\Console\Commands;

use App\Services\LocalResumeAnalyzer;
use App\Services\ResumeTextExtractor;
use Illuminate\Console\Command;

class CheckLocalAi extends Command
{
    protected $signature = 'point:ai-check {--demo : Analisa o PDF fictício de demonstração sem gravar no banco}';

    protected $description = 'Verifica o Ollama e o modelo local de análise de currículos';

    public function handle(LocalResumeAnalyzer $ai): int
    {
        $health = $ai->health();
        $this->line('Modelo: '.$health['model']);
        if (! $health['ready']) {
            $this->error($health['message']);

            return self::FAILURE;
        }
        $this->info($health['message']);
        if ($this->option('demo')) {
            $this->line('Testando inferência real com dados fictícios...');
            $start = microtime(true);
            $text = app(ResumeTextExtractor::class)->extract(base_path('output/pdf/curriculo-demonstracao.pdf'));
            $result = $ai->analyze($text,
                ['Experiência com Laravel', '5 anos de experiência em Laravel', 'Conhecimento em MySQL', 'Inglês fluente', 'Experiência com Docker'], 'Desenvolvedor Laravel');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info('Inferência concluída em '.round(microtime(true) - $start).' segundos.');
        }

        return self::SUCCESS;
    }
}
