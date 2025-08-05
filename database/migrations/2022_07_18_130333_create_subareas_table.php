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
        Schema::create('subareas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->unique()->nullable();
            $table->text('description')->nullable();
            $table->foreignId('default_area_id')->nullable()->constrained('areas');
            $table->integer('default_input_weight')->default(0);
            $table->double('default_weight', 8, 2)->default(0);
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
        Schema::dropIfExists('subareas');
    }
};
