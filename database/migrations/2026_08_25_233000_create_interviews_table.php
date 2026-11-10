<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
                $table->timestamp('scheduled_at');
                $table->string('format', 40)->default('online');
                $table->string('location')->nullable();
                $table->string('meeting_url')->nullable();
                $table->text('candidate_instructions')->nullable();
                $table->text('internal_notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
