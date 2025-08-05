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
        DB::statement("ALTER TABLE indicators DROP FOREIGN KEY indicators_default_subarea_id_foreign;");
        DB::statement("ALTER TABLE indicators ADD CONSTRAINT indicators_default_subarea_id_foreign FOREIGN KEY (default_subarea_id) REFERENCES subareas(id) ON DELETE CASCADE;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE indicators DROP FOREIGN KEY indicators_default_subarea_id_foreign;");
        DB::statement("ALTER TABLE indicators ADD CONSTRAINT indicators_default_subarea_id_foreign FOREIGN KEY (default_subarea_id) REFERENCES subareas(id);");
    }
};
