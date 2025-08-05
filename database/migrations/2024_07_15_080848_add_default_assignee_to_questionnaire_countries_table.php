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
        Schema::table('questionnaire_countries', function (Blueprint $table) {
            $table->foreignId('default_assignee')->nullable(true)->after('id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaire_countries', function (Blueprint $table) {
            $table->dropColumn('default_assignee');
        });
    }
};
