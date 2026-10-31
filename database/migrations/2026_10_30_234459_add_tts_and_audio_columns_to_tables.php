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
        Schema::table('languages', function (Blueprint $table) {
            $table->string('tts_locale', 30)->nullable()->after('direction');
            $table->string('tts_voice', 100)->nullable()->after('tts_locale');
        });

        Schema::table('vocabulary_entries', function (Blueprint $table) {
            $table->string('audio_path', 255)->nullable()->after('note');
            $table->string('audio_source', 50)->nullable()->after('audio_path'); // 'azure_tts', 'manual'
            $table->string('audio_voice', 100)->nullable()->after('audio_source');
            $table->string('audio_hash', 64)->nullable()->after('audio_voice');
            $table->unsignedInteger('audio_char_count')->default(0)->after('audio_hash');
        });

        Schema::create('tts_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vocabulary_entry_id')->nullable()->constrained('vocabulary_entries')->nullOnDelete();
            $table->unsignedInteger('characters_used')->default(0);
            $table->string('locale', 30)->nullable();
            $table->string('voice', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tts_usages');

        Schema::table('vocabulary_entries', function (Blueprint $table) {
            $table->dropColumn([
                'audio_path',
                'audio_source',
                'audio_voice',
                'audio_hash',
                'audio_char_count',
            ]);
        });

        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn([
                'tts_locale',
                'tts_voice',
            ]);
        });
    }
};
