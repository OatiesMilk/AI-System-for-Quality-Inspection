<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames the 'finishing' checkpoint/stage value to 'pre_assembly',
     * reflecting the redesigned workflow: this inspection point now checks
     * the flat 2D leather pieces right before upper-making, not the
     * finished 3D shoe. On MySQL, `inspections.checkpoint` is a native ENUM
     * and must be relaxed before it will accept the new value; on SQLite
     * it's already an unconstrained varchar, so only the data needs
     * backfilling there. `batches.manufacturing_stage` is a plain string on
     * both drivers (validated at the app layer only), so it only needs the
     * data backfill.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inspections MODIFY checkpoint VARCHAR(255) NOT NULL COMMENT 'Which inspection station along the line per the conceptual framework'");
        }

        DB::table('inspections')->where('checkpoint', 'finishing')->update(['checkpoint' => 'pre_assembly']);
        DB::table('batches')->where('manufacturing_stage', 'finishing')->update(['manufacturing_stage' => 'pre_assembly']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inspections')->where('checkpoint', 'pre_assembly')->update(['checkpoint' => 'finishing']);
        DB::table('batches')->where('manufacturing_stage', 'pre_assembly')->update(['manufacturing_stage' => 'finishing']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inspections MODIFY checkpoint ENUM('preparation','finishing') NOT NULL COMMENT 'Which inspection station along the line per the conceptual framework'");
        }
    }
};
