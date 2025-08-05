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
        Schema::create('indicator_accordion_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accordion_id')->constrained('indicator_accordions')->onDelete('cascade');
            $table->text('title');
            $table->integer('order');
            $table->foreignId('type_id')->constrained('indicator_question_types');
            $table->text('info')->nullable();
            $table->boolean('compatible')->default(true);
            $table->boolean('answers_required')->default(true);
            $table->boolean('reference_required')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_accordion_questions');
    }
};
