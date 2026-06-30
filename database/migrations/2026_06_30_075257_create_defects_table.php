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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defects');
    }
};
