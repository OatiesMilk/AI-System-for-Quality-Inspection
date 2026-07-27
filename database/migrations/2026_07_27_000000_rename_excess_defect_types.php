<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rebuilds the table (rename -> create -> copy -> drop) rather than a raw
     * `ALTER TABLE ... MODIFY` - that syntax is MySQL-only and breaks the test
     * suite, which runs on SQLite. Schema::create/rename/drop work identically
     * on both drivers, so this approach is portable.
     */
    public function up(): void
    {
        Schema::rename('defects', 'defects_old');

        Schema::create('defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->enum('defect_type', [
                'scratch',
                'cut',
                'hole',
                'crease',
                'glue',
                'stitch',
            ]);
            $table->decimal('confidence_score', 5, 4)->comment('YOLO model confidence, 0-1');
            $table->json('bounding_box')->nullable()->comment('x, y, width, height from YOLO output');
            $table->boolean('confirmed')->nullable()
                ->comment('Inspector HITL confirmation: true=confirmed, false=dismissed, null=pending');
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO defects (id, inspection_id, defect_type, confidence_score, bounding_box, confirmed, created_at, updated_at)
            SELECT id, inspection_id,
                   CASE defect_type
                       WHEN 'excess_glue' THEN 'glue'
                       WHEN 'excess_stitch' THEN 'stitch'
                       ELSE defect_type
                   END,
                   confidence_score, bounding_box, confirmed, created_at, updated_at
            FROM defects_old
        ");

        Schema::drop('defects_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('defects', 'defects_old');

        Schema::create('defects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->enum('defect_type', [
                'scratch',
                'cut',
                'hole',
                'crease',
                'excess_glue',
                'excess_stitch',
            ]);
            $table->decimal('confidence_score', 5, 4)->comment('YOLO model confidence, 0-1');
            $table->json('bounding_box')->nullable()->comment('x, y, width, height from YOLO output');
            $table->boolean('confirmed')->nullable()
                ->comment('Inspector HITL confirmation: true=confirmed, false=dismissed, null=pending');
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO defects (id, inspection_id, defect_type, confidence_score, bounding_box, confirmed, created_at, updated_at)
            SELECT id, inspection_id,
                   CASE defect_type
                       WHEN 'glue' THEN 'excess_glue'
                       WHEN 'stitch' THEN 'excess_stitch'
                       ELSE defect_type
                   END,
                   confidence_score, bounding_box, confirmed, created_at, updated_at
            FROM defects_old
        ");

        Schema::drop('defects_old');
    }
};
