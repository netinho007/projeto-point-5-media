<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->index(['status', 'published_at', 'closes_at'], 'vacancies_public_listing_index');
            $table->index(['status', 'department'], 'vacancies_public_department_index');
            $table->index(['status', 'employment_type'], 'vacancies_public_employment_index');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex('vacancies_public_listing_index');
            $table->dropIndex('vacancies_public_department_index');
            $table->dropIndex('vacancies_public_employment_index');
        });
    }
};
