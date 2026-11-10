<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'github_url')) $table->string('github_url')->nullable();
            if (! Schema::hasColumn('candidates', 'website_url')) $table->string('website_url')->nullable();
            if (! Schema::hasColumn('candidates', 'behance_url')) $table->string('behance_url')->nullable();
            if (! Schema::hasColumn('candidates', 'contract_preference')) $table->string('contract_preference', 80)->nullable();
            if (! Schema::hasColumn('candidates', 'travel_availability')) $table->boolean('travel_availability')->default(false);
            if (! Schema::hasColumn('candidates', 'relocation_availability')) $table->boolean('relocation_availability')->default(false);
        });

        if (! Schema::hasTable('candidate_educations')) {
            Schema::create('candidate_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('institution');
                $table->string('course');
                $table->string('degree', 80)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('current')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_projects')) {
            Schema::create('candidate_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('technologies')->nullable();
                $table->string('project_url')->nullable();
                $table->string('repository_url')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('candidate_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_messages', 'attachment_path')) $table->string('attachment_path')->nullable();
            if (! Schema::hasColumn('candidate_messages', 'attachment_name')) $table->string('attachment_name')->nullable();
            if (! Schema::hasColumn('candidate_messages', 'attachment_mime')) $table->string('attachment_mime')->nullable();
        });

        Schema::table('interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('interviews', 'candidate_response')) $table->string('candidate_response', 30)->nullable();
            if (! Schema::hasColumn('interviews', 'candidate_response_at')) $table->timestamp('candidate_response_at')->nullable();
        });

        Schema::table('application_analyses', function (Blueprint $table) {
            if (! Schema::hasColumn('application_analyses', 'languages')) $table->json('languages')->nullable();
            if (! Schema::hasColumn('application_analyses', 'certifications')) $table->json('certifications')->nullable();
            if (! Schema::hasColumn('application_analyses', 'profile_highlights')) $table->json('profile_highlights')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_projects');
        Schema::dropIfExists('candidate_educations');

        Schema::table('candidate_messages', function (Blueprint $table) {
            foreach (['attachment_path','attachment_name','attachment_mime'] as $column) {
                if (Schema::hasColumn('candidate_messages', $column)) $table->dropColumn($column);
            }
        });

        Schema::table('interviews', function (Blueprint $table) {
            foreach (['candidate_response','candidate_response_at'] as $column) {
                if (Schema::hasColumn('interviews', $column)) $table->dropColumn($column);
            }
        });

        Schema::table('application_analyses', function (Blueprint $table) {
            foreach (['languages','certifications','profile_highlights'] as $column) {
                if (Schema::hasColumn('application_analyses', $column)) $table->dropColumn($column);
            }
        });

        Schema::table('candidates', function (Blueprint $table) {
            foreach (['github_url','website_url','behance_url','contract_preference','travel_availability','relocation_availability'] as $column) {
                if (Schema::hasColumn('candidates', $column)) $table->dropColumn($column);
            }
        });
    }
};
