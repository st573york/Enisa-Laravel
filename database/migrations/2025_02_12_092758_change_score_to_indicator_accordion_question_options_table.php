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
        Schema::table('indicator_accordion_question_options', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('indicator_accordion_question_options', function (Blueprint $table) {
            $table->double('score', 8, 2)->nullable()->after('master');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicator_accordion_question_options', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('indicator_accordion_question_options', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('master');
        });
    }
};
