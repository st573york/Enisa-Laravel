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
        Schema::create('baseline_indices', function (Blueprint $table) {
            $table->id();           
            $table->string('name');
            $table->text('description');
            $table->foreignId('index_configuration_id')->constrained('index_configurations');
            $table->json('json_data');            
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
        Schema::dropIfExists('baseline_indices');
    }
};
