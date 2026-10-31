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
        Schema::table('ai_generated_clozes', function (Blueprint $table) {
            $table->text('translation_with_blank')->nullable()->after('translation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generated_clozes', function (Blueprint $table) {
            $table->dropColumn('translation_with_blank');
        });
    }
};
