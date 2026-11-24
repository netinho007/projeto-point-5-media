<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('resume_original_name')->nullable()->after('resume_path');
            $table->string('resume_mime')->nullable()->after('resume_original_name');
            $table->unsignedBigInteger('resume_size')->nullable()->after('resume_mime');
            $table->timestamp('consent_at')->nullable()->after('analyzed_at');

        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'resume_original_name',
                'resume_mime',
                'resume_size',
                'consent_at',
            ]);
        });
    }
};
