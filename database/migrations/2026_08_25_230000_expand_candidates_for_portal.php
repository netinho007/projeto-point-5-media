<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('candidates', 'user_id')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        $columns = [
            'headline', 'preferred_area', 'linkedin_url', 'portfolio_url', 'bio',
            'resume_path', 'resume_original_name', 'resume_mime', 'resume_size',
            'talent_pool', 'talent_pool_consent_at', 'privacy_consent_at', 'profile_completed_at',
        ];

        $missing = array_filter($columns, fn (string $column) => ! Schema::hasColumn('candidates', $column));

        if ($missing !== []) {
            Schema::table('candidates', function (Blueprint $table) use ($missing) {
                if (in_array('headline', $missing, true)) {
                    $table->string('headline')->nullable()->after('state');
                }
                if (in_array('preferred_area', $missing, true)) {
                    $table->string('preferred_area')->nullable()->after('headline');
                }
                if (in_array('linkedin_url', $missing, true)) {
                    $table->string('linkedin_url')->nullable()->after('preferred_area');
                }
                if (in_array('portfolio_url', $missing, true)) {
                    $table->string('portfolio_url')->nullable()->after('linkedin_url');
                }
                if (in_array('bio', $missing, true)) {
                    $table->text('bio')->nullable()->after('portfolio_url');
                }
                if (in_array('resume_path', $missing, true)) {
                    $table->string('resume_path')->nullable()->after('bio');
                }
                if (in_array('resume_original_name', $missing, true)) {
                    $table->string('resume_original_name')->nullable()->after('resume_path');
                }
                if (in_array('resume_mime', $missing, true)) {
                    $table->string('resume_mime')->nullable()->after('resume_original_name');
                }
                if (in_array('resume_size', $missing, true)) {
                    $table->unsignedBigInteger('resume_size')->nullable()->after('resume_mime');
                }
                if (in_array('talent_pool', $missing, true)) {
                    $table->boolean('talent_pool')->default(false)->after('resume_size');
                }
                if (in_array('talent_pool_consent_at', $missing, true)) {
                    $table->timestamp('talent_pool_consent_at')->nullable()->after('talent_pool');
                }
                if (in_array('privacy_consent_at', $missing, true)) {
                    $table->timestamp('privacy_consent_at')->nullable()->after('talent_pool_consent_at');
                }
                if (in_array('profile_completed_at', $missing, true)) {
                    $table->timestamp('profile_completed_at')->nullable()->after('privacy_consent_at');
                }
            });
        }
    }

    public function down(): void
    {
        // Migration de atualização segura: não removemos colunas para evitar perda de dados.
    }
};
