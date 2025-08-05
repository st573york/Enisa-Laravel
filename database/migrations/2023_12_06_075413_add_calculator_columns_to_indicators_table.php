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
        Schema::table('indicators', function (Blueprint $table) {
            $table->double('direction')->nullable()->after('category');
            $table->boolean('new_indicator')->nullable(true)->after('direction');
            $table->boolean('min_max_0037_1')->nullable(true)->after('new_indicator');
            $table->boolean('min_max')->nullable(true)->after('min_max_0037_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn('direction');
            $table->dropColumn('new_indicator');
            $table->dropColumn('min_max_0037_1');
            $table->dropColumn('min_max');
        });
    }
};
