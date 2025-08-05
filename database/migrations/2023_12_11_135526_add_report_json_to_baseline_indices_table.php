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
        Schema::table('baseline_indices', function (Blueprint $table) {
            $table->json('report_json')->nullable()->after('json_data');
            $table->timestamp('report_date')->nullable()->after('report_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baseline_indices', function (Blueprint $table) {
            $table->dropColumn('report_json');
            $table->dropColumn('report_date');
        });
    }
};
