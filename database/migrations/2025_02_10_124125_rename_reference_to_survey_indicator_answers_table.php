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
            $table->renameColumn('reference', 'reference_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_indicator_answers', function (Blueprint $table) {
            $table->renameColumn('reference_source', 'reference');
        });
    }
};
