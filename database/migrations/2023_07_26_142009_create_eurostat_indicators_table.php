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
        Schema::create('eurostat_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('source')->nullable();
            $table->text('identifier')->nullable();
            $table->foreignId('country_id')->constrained('countries');
            $table->text('report_year')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eurostat_indicators');
    }
};
