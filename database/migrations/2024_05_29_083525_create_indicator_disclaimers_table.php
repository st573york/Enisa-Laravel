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
        Schema::create('indicator_disclaimers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->onDelete('cascade');
            $table->text('what_100_means_eu')->nullable();
            $table->text('what_100_means_ms')->nullable();
            $table->boolean('frac_max_norm')->nullable(true);
            $table->boolean('rank_norm')->nullable(true);
            $table->boolean('target_100')->nullable(true);
            $table->boolean('target_75')->nullable(true);
            $table->double('direction')->nullable();
            $table->boolean('new_indicator')->nullable(true);
            $table->boolean('min_max_0037_1')->nullable(true);
            $table->boolean('min_max')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicator_disclaimers');
    }
};
