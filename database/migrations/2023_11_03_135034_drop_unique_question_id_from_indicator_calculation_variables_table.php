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
        Schema::table('indicator_calculation_variables', function (Blueprint $table) {
            $table->dropUnique(['question_id']);
            $table->unique(['indicator_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicator_calculation_variables', function (Blueprint $table) {
            $table->unique('question_id');
            $table->dropUnique(['indicator_id', 'question_id']);
        });
    }
};
