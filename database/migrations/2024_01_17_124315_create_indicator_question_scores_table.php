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
        Schema::create('indicator_question_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('indicator_id')->constrained('indicators');
            $table->uuid('question_id');
            $table->unique(['country_id', 'indicator_id', 'question_id'], 'indicator_question_scores_country_indicator_question_unique');
            $table->double('score');
            $table->boolean('data_not_available')->default(false);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_question_scores');
    }
};
