<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_token', 64)->index();
            $table->string('path', 500)->nullable();
            $table->date('visited_on')->index();
            $table->timestamps();

            $table->index(['visited_on', 'visitor_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
