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
        Schema::create('indicator_calculation_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators');
            $table->uuid('question_id')->unique();
            $table->mediumText('algorithm')->nullable();
            $table->string('type');
            $table->integer('predefined_divider')->nullable();
            $table->boolean('normalize')->default(false);
            $table->integer('inverse_value')->nullable();
            $table->text('custom_function_name')->nullable();
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
        Schema::dropIfExists('indicator_calculation_variables');
    }
};
