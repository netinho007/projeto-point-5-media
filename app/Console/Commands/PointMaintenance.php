<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateNotification;
use App\Models\Vacancy;
use Illuminate\Console\Command;

class PointMaintenance extends Command
{
    protected $signature = 'point:maintenance';

    protected $description = 'Atualiza prazos de vagas e consentimentos do Banco de Talentos.';

    public function handle(): int
    {
        $closedVacancies = Vacancy::query()
            ->where('status', 'open')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<', now())
            ->update(['status' => 'closed']);

        $expiredTalentProfiles = 0;

        Candidate::query()
            ->where('talent_pool', true)
            ->whereNotNull('talent_pool_expires_at')
            ->where('talent_pool_expires_at', '<', now())
            ->chunkById(100, function ($candidates) use (&$expiredTalentProfiles) {
                foreach ($candidates as $candidate) {
                    $candidate->update(['talent_pool' => false]);

                    CandidateNotification::create([
                        'candidate_id' => $candidate->id,
                        'type' => 'privacy',
                        'title' => 'Consentimento do Banco de Talentos encerrado',
                        'message' => 'O período de permanência no Banco de Talentos terminou. Você pode autorizar novamente pelo seu perfil.',
                    ]);

                    $expiredTalentProfiles++;
                }
            });

        $this->info("Vagas encerradas: {$closedVacancies}");
        $this->info("Perfis retirados do Banco de Talentos por expiração: {$expiredTalentProfiles}");

        return self::SUCCESS;
    }
}
