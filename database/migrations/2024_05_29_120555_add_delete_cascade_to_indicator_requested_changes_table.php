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
        DB::statement("ALTER TABLE indicator_requested_changes DROP FOREIGN KEY indicator_requested_changes_indicator_id_foreign;");
        DB::statement("ALTER TABLE indicator_requested_changes ADD CONSTRAINT indicator_requested_changes_indicator_id_foreign FOREIGN KEY (indicator_id) REFERENCES indicators(id) ON DELETE CASCADE;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE indicator_requested_changes DROP FOREIGN KEY indicator_requested_changes_indicator_id_foreign;");
        DB::statement("ALTER TABLE indicator_requested_changes ADD CONSTRAINT indicator_requested_changes_indicator_id_foreign FOREIGN KEY (indicator_id) REFERENCES indicators(id);");
    }
};
