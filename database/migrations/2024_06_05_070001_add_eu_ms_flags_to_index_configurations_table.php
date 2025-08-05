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
        Schema::table('index_configurations', function (Blueprint $table) {
            $table->boolean('eu_published')->default(false)->after('json_data');
            $table->boolean('ms_published')->default(false)->after('eu_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('index_configurations', function (Blueprint $table) {
            $table->dropColumn('eu_published');
            $table->dropColumn('ms_published');
        });
    }
};
