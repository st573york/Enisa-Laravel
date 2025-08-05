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
        Schema::create('survey_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_country_id')->constrained('questionnaire_countries');
            $table->foreignId('indicator_id')->constrained('indicators');
            $table->foreignId('assignee')->constrained('users');
            $table->foreignId('state_id')->constrained('indicator_statuses');
            $table->foreignId('dashboard_state_id')->constrained('indicator_statuses');
            $table->integer('rating')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('answers_loaded')->default(false);
            $table->date('deadline')->nullable();
            $table->timestamp('last_saved')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_indicators');
    }
};
