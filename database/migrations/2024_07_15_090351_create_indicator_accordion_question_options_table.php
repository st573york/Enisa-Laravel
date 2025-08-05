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
        Schema::create('indicator_accordion_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('indicator_accordion_questions')->onDelete('cascade');
            $table->text('text');
            $table->boolean('master')->default(false);
            $table->integer('score')->nullable();
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_accordion_question_options');
    }
};
