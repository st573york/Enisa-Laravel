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
        Schema::create('eurostat_indicator_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eurostat_indicator_id')->constrained('eurostat_indicators');
            $table->foreignId('country_id')->constrained('countries');
            $table->text('variable_identifier')->nullable();
            $table->text('variable_code')->nullable();
            $table->text('variable_name')->nullable();
            $table->double('variable_value')->nullable();
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
        Schema::dropIfExists('eurostat_indicator_variables');
    }
};
