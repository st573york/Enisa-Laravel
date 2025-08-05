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
        Schema::create('survey_indicator_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_indicator_id')->constrained('survey_indicators');
            $table->foreignId('question_id')->constrained('indicator_accordion_questions');
            $table->foreignId('choice_id')->constrained('indicator_question_choices');
            $table->text('free_text')->nullable();
            $table->text('reference')->nullable();
            $table->timestamp('last_saved')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_indicator_answers');
    }
};
