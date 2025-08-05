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
        Schema::create('questionnaire_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->nullable(true)->constrained('users');
            $table->foreignId('questionnaire_id')->constrained('questionnaires');
            $table->foreignId('country_id')->constrained('countries');
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
        Schema::dropIfExists('questionnaire_countries');
    }
};
