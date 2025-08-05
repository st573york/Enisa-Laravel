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
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->unique()->nullable(true);
            $table->text('description')->nullable();
            $table->foreignId('default_subarea_id')->nullable()->constrained('subareas');
            $table->integer('default_input_weight')->default(0);
            $table->double('default_weight', 8, 2)->default(0);
            $table->json('configuration_json')->nullable(true);
            $table->boolean('manual_input')->nullable(true);
            $table->text('algorithm')->nullable();
            $table->text('source')->nullable();
            $table->text('note')->nullable();
            $table->integer('identifier')->nullable();
            $table->text('format')->nullable();
            $table->text('report_year')->nullable();
            $table->text('graphs')->nullable();
            $table->text('category')->nullable();
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
        Schema::dropIfExists('indicators');
    }
};
