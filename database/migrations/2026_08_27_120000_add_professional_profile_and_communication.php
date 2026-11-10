<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'profile_photo_path')) $table->string('profile_photo_path')->nullable();
            if (! Schema::hasColumn('candidates', 'current_role')) $table->string('current_role')->nullable();
            if (! Schema::hasColumn('candidates', 'desired_role')) $table->string('desired_role')->nullable();
            if (! Schema::hasColumn('candidates', 'years_experience')) $table->unsignedTinyInteger('years_experience')->nullable();
            if (! Schema::hasColumn('candidates', 'availability')) $table->string('availability', 80)->nullable();
            if (! Schema::hasColumn('candidates', 'work_mode')) $table->string('work_mode', 40)->nullable();
            if (! Schema::hasColumn('candidates', 'salary_expectation')) $table->decimal('salary_expectation', 12, 2)->nullable();
            if (! Schema::hasColumn('candidates', 'phone_verified_at')) $table->timestamp('phone_verified_at')->nullable();
            if (! Schema::hasColumn('candidates', 'phone_verification_hash')) $table->string('phone_verification_hash')->nullable();
            if (! Schema::hasColumn('candidates', 'phone_verification_expires_at')) $table->timestamp('phone_verification_expires_at')->nullable();
            if (! Schema::hasColumn('candidates', 'phone_verification_attempts')) $table->unsignedTinyInteger('phone_verification_attempts')->default(0);
            if (! Schema::hasColumn('candidates', 'contact_preferences')) $table->json('contact_preferences')->nullable();
            if (! Schema::hasColumn('candidates', 'account_active')) $table->boolean('account_active')->default(true);
            if (! Schema::hasColumn('candidates', 'deletion_requested_at')) $table->timestamp('deletion_requested_at')->nullable();
            if (! Schema::hasColumn('candidates', 'talent_pool_expires_at')) $table->timestamp('talent_pool_expires_at')->nullable();
        });

        Schema::table('applications', function (Blueprint $table) {
            if (! Schema::hasColumn('applications', 'cover_letter')) $table->text('cover_letter')->nullable();
            if (! Schema::hasColumn('applications', 'referral_name')) $table->string('referral_name')->nullable();
            if (! Schema::hasColumn('applications', 'source')) $table->string('source', 80)->nullable();
            if (! Schema::hasColumn('applications', 'withdrawn_at')) $table->timestamp('withdrawn_at')->nullable();
        });

        Schema::table('vacancies', function (Blueprint $table) {
            if (! Schema::hasColumn('vacancies', 'work_mode')) $table->string('work_mode', 40)->nullable();
            if (! Schema::hasColumn('vacancies', 'published_at')) $table->timestamp('published_at')->nullable();
            if (! Schema::hasColumn('vacancies', 'closes_at')) $table->timestamp('closes_at')->nullable();
            if (! Schema::hasColumn('vacancies', 'referral_enabled')) $table->boolean('referral_enabled')->default(true);
        });

        if (! Schema::hasTable('candidate_languages')) {
            Schema::create('candidate_languages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('language', 100);
                $table->string('level', 40);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_certifications')) {
            Schema::create('candidate_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('issuer')->nullable();
                $table->date('issued_at')->nullable();
                $table->date('expires_at')->nullable();
                $table->string('credential_id')->nullable();
                $table->string('credential_url')->nullable();
                $table->string('file_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_courses')) {
            Schema::create('candidate_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('institution')->nullable();
                $table->unsignedInteger('workload_hours')->nullable();
                $table->date('completed_at')->nullable();
                $table->string('certificate_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_experiences')) {
            Schema::create('candidate_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('company');
                $table->string('role');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('current')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_documents')) {
            Schema::create('candidate_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->string('type', 80)->default('other');
                $table->string('title');
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_messages')) {
            Schema::create('candidate_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sender_role', 20);
                $table->string('subject')->nullable();
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['candidate_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('saved_vacancies')) {
            Schema::create('saved_vacancies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['candidate_id', 'vacancy_id']);
            });
        }

        if (! Schema::hasTable('candidate_tags')) {
            Schema::create('candidate_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('candidate_candidate_tag')) {
            Schema::create('candidate_candidate_tag', function (Blueprint $table) {
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('candidate_tag_id')->constrained()->cascadeOnDelete();
                $table->primary(['candidate_id', 'candidate_tag_id']);
            });
        }

        if (! Schema::hasTable('favorite_candidates')) {
            Schema::create('favorite_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'candidate_id']);
            });
        }

        if (! Schema::hasTable('message_templates')) {
            Schema::create('message_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('channel', 30)->default('portal');
                $table->string('subject')->nullable();
                $table->text('body');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('admin_saved_filters')) {
            Schema::create('admin_saved_filters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('context', 40);
                $table->json('filters');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_saved_filters');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('favorite_candidates');
        Schema::dropIfExists('candidate_candidate_tag');
        Schema::dropIfExists('candidate_tags');
        Schema::dropIfExists('saved_vacancies');
        Schema::dropIfExists('candidate_messages');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidate_experiences');
        Schema::dropIfExists('candidate_courses');
        Schema::dropIfExists('candidate_certifications');
        Schema::dropIfExists('candidate_languages');
    }
};
