<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE survey_indicator_options DROP FOREIGN KEY survey_indicator_options_survey_indicator_id_foreign;");
        DB::statement("ALTER TABLE survey_indicator_options ADD CONSTRAINT survey_indicator_options_survey_indicator_id_foreign FOREIGN KEY (survey_indicator_id) REFERENCES survey_indicators(id) ON DELETE CASCADE;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE survey_indicator_options DROP FOREIGN KEY survey_indicator_options_survey_indicator_id_foreign;");
        DB::statement("ALTER TABLE survey_indicator_options ADD CONSTRAINT survey_indicator_options_survey_indicator_id_foreign FOREIGN KEY (survey_indicator_id) REFERENCES survey_indicators(id);");
    }
};
