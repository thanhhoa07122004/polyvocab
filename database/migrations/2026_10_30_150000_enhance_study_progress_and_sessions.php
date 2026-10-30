<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('study_records', function (Blueprint $table) {
            $table->timestamp('familiarized_at')->nullable()->after('entry_meaning_id');
            $table->index(['user_id', 'familiarized_at'], 'study_rec_user_familiarized_idx');
        });

        // Bảo toàn dữ liệu cũ: những bản ghi đã tồn tại được coi là đã làm quen
        DB::table('study_records')
            ->whereNull('familiarized_at')
            ->update(['familiarized_at' => DB::raw('created_at')]);

        Schema::table('study_session_items', function (Blueprint $table) {
            $table->string('first_attempt_result', 20)->nullable()->after('practice_result');
            $table->unsignedInteger('attempts_count')->default(0)->after('first_attempt_result');
            $table->boolean('used_hint')->default(false)->after('attempts_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_session_items', function (Blueprint $table) {
            $table->dropColumn(['first_attempt_result', 'attempts_count', 'used_hint']);
        });

        Schema::table('study_records', function (Blueprint $table) {
            $table->dropIndex('study_rec_user_familiarized_idx');
            $table->dropColumn('familiarized_at');
        });
    }
};

