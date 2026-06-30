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
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('checkpoint', ['preparation', 'finishing'])
                ->comment('Which inspection station along the line per the conceptual framework');
            $table->string('image_path')->nullable();
            $table->enum('action', ['pass', 'rework', 'reject'])->nullable()
                ->comment('Final human-validated decision (HITL)');
            $table->boolean('ai_override')->default(false)
                ->comment('True if the inspector overrode the AI recommendation');
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
