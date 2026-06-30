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
        Schema::table('inspections', function (Blueprint $table) {
            $table->longText('image_data')->nullable()->after('image_path')
                ->comment('Base64-encoded image bytes, stored in the DB so every machine on the shared database can see it without a separate file share');
            $table->string('image_mime')->nullable()->after('image_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'image_mime']);
        });
    }
};
