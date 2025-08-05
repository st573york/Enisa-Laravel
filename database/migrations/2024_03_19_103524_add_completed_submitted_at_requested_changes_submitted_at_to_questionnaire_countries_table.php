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
            $table->boolean('completed')->default(false)->after('id');
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->timestamp('requested_changes_submitted_at')->nullable()->after('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaire_countries', function (Blueprint $table) {
            $table->dropColumn('completed');
            $table->dropColumn('submitted_at');
            $table->dropColumn('requested_changes_submitted_at');
        });
    }
};
