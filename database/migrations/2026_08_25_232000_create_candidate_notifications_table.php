<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_notifications')) {
            Schema::create('candidate_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 60)->default('status');
                $table->string('title');
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['candidate_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_notifications');
    }
};
