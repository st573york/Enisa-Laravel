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
        DB::statement("ALTER TABLE indicator_calculation_variables DROP FOREIGN KEY indicator_calculation_variables_indicator_id_foreign;");
        DB::statement("ALTER TABLE indicator_calculation_variables ADD CONSTRAINT indicator_calculation_variables_indicator_id_foreign FOREIGN KEY (indicator_id) REFERENCES indicators(id) ON DELETE CASCADE;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE indicator_calculation_variables DROP FOREIGN KEY indicator_calculation_variables_indicator_id_foreign;");
        DB::statement("ALTER TABLE indicator_calculation_variables ADD CONSTRAINT indicator_calculation_variables_indicator_id_foreign FOREIGN KEY (indicator_id) REFERENCES indicators(id);");
    }
};
