<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('indices', function (Blueprint $table) {
            $table->json('report_json')->nullable()->after('json_data');
            $table->timestamp('report_date')->nullable()->after('report_json');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('indices', function (Blueprint $table) {
            $table->dropColumn('report_json');
            $table->dropColumn('report_date');
        });
    }
};
