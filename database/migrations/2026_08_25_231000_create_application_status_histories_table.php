<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_status_histories')) {
            Schema::create('application_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained()->cascadeOnDelete();
                $table->string('status', 40);
                $table->string('public_note', 500)->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['application_id', 'created_at']);
            });

            DB::table('applications')->orderBy('id')->chunkById(200, function ($applications) {
                foreach ($applications as $application) {
                    DB::table('application_status_histories')->insert([
                        'application_id' => $application->id,
                        'status' => $application->status ?: 'received',
                        'public_note' => 'Status existente antes da ativação do Portal do Candidato.',
                        'changed_by' => null,
                        'created_at' => $application->created_at ?: now(),
                        'updated_at' => $application->created_at ?: now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
    }
};
