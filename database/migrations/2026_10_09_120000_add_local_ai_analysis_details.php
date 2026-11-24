<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_analyses', function (Blueprint $table) {
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->uuid('run_id')->nullable();
            $table->json('requirement_evidence')->nullable();
            $table->json('interview_questions')->nullable();
            $table->json('limitations')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('application_analyses', function (Blueprint $table) {
            $table->dropColumn(['provider', 'model', 'run_id', 'requirement_evidence', 'interview_questions', 'limitations', 'duration_ms', 'started_at']);
        });
    }
};
