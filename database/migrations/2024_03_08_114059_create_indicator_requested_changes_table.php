<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indicator_requested_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_country_id')->constrained('questionnaire_countries');
            $table->foreignId('indicator_id')->constrained('indicators');
            $table->longText('changes');
            $table->date('deadline');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('requested_to')->constrained('users');
            $table->timestamp('requested_at');
            $table->timestamp('answered_at')->nullable();
            $table->foreignId('state')->constrained('indicator_requested_change_statuses');
            $table->foreignId('indicator_previous_state')->constrained('indicator_statuses');
            $table->foreignId('indicator_previous_assignee')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_requested_changes');
    }
};
