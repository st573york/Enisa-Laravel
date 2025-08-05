<?php

use App\Models\Indicator;
use App\Models\IndicatorDisclaimer;
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
        $this->migrateData();

        Schema::table('indicators', function (Blueprint $table) {
            $table->dropColumn('direction');
            $table->dropColumn('new_indicator');
            $table->dropColumn('min_max_0037_1');
            $table->dropColumn('min_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicators', function (Blueprint $table) {
            $table->double('direction')->nullable();
            $table->boolean('new_indicator')->default(false);
            $table->boolean('min_max_0037_1')->default(false);
            $table->boolean('min_max')->default(false);
        });
    }

    private function migrateData()
    {
        $indicators = Indicator::all();

        foreach ($indicators as $indicator)
        {
            $data = [
                'indicator_id' => $indicator->id,
                'direction' => $indicator->direction,
                'new_indicator' => $indicator->new_indicator,
                'min_max_0037_1' => $indicator->min_max_0037_1,
                'min_max' => $indicator->min_max
            ];

            IndicatorDisclaimer::create($data);
        }
    }
};
