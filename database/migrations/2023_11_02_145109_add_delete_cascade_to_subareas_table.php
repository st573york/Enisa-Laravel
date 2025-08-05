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
        DB::statement("ALTER TABLE subareas DROP FOREIGN KEY subareas_default_area_id_foreign;");
        DB::statement("ALTER TABLE subareas ADD CONSTRAINT subareas_default_area_id_foreign FOREIGN KEY (default_area_id) REFERENCES areas(id) ON DELETE CASCADE;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE subareas DROP FOREIGN KEY subareas_default_area_id_foreign;");
        DB::statement("ALTER TABLE subareas ADD CONSTRAINT subareas_default_area_id_foreign FOREIGN KEY (default_area_id) REFERENCES areas(id);");
    }
};
