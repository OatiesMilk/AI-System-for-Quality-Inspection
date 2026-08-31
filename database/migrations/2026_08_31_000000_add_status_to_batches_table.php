<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks whether a batch is still actively receiving pieces ('open') or
     * has finished intake ('completed'), either automatically once produced
     * pieces reach expected_pieces, or manually closed by a manager. This is
     * what lets the vision pipeline's "latest batch" lookup act as a real
     * FIFO queue instead of just grabbing whichever batch row is newest.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->enum('status', ['open', 'completed'])
                ->default('open')
                ->after('expected_pieces')
                ->comment('open = still receiving pieces at its checkpoint; completed = intake finished (auto or manual)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
