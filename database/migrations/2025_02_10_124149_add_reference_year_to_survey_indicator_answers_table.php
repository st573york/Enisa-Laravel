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
        Schema::table('survey_indicator_answers', function (Blueprint $table) {
            $table->integer('reference_year')->nullable()->after('free_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_indicator_answers', function (Blueprint $table) {
            $table->dropColumn('reference_year');
        });
    }
};
