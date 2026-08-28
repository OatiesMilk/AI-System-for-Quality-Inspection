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
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->enum('shift', ['am', 'pm'])
                ->nullable()
                ->after('production_date')
                ->comment('Production shift this batch was assembled in (Batch 1 = AM, Batch 2 = PM)');
        });
    }
};
