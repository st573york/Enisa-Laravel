<?php

use App\Models\Subarea;
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
        Schema::table('subareas', function (Blueprint $table) {
            $table->text('year')->nullable();
        });

        Subarea::query()->update(['year' => '2022']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subareas', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
