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
        Schema::create('study_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_meaning_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('repetition_level')->default(0);
            $table->decimal('ease_factor', 5, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('lapse_count')->default(0);
            $table->boolean('is_self_declared_known')->default(false);
            $table->boolean('is_difficult')->default(false);
            $table->timestamp('last_studied_at')->nullable();
            $table->timestamps();

            // Mỗi người học có duy nhất 1 bản ghi tiến độ cho 1 nghĩa cụ thể
            $table->unique(['user_id', 'entry_meaning_id'], 'study_rec_user_meaning_uq');
            $table->index(['user_id', 'due_at'], 'study_rec_user_due_idx');
            $table->index(['user_id', 'deck_id', 'due_at'], 'study_rec_user_deck_due_idx');
        });

        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 30)->default('learn_new');
            $table->string('stage', 30)->default('familiarization');
            $table->string('status', 30)->default('in_progress');
            $table->unsignedInteger('chunk_size')->default(5);
            $table->unsignedInteger('current_chunk')->default(0);
            $table->json('settings')->nullable();
            $table->timestamp('last_interacted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'study_sess_user_status_idx');
            $table->index(['user_id', 'deck_id', 'status'], 'study_sess_user_deck_status_idx');
        });

        Schema::create('study_session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entry_meaning_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('chunk_index')->default(0);
            $table->string('stage', 30)->default('familiarization');
            $table->timestamp('familiarized_at')->nullable();
            $table->timestamp('practiced_at')->nullable();
            $table->boolean('is_difficult')->default(false);
            $table->boolean('is_self_declared_known')->default(false);
            $table->string('practice_result', 20)->nullable();
            $table->timestamps();

            $table->index(['study_session_id', 'chunk_index', 'sort_order'], 'ss_items_chunk_sort_idx');
            $table->index(['study_session_id', 'entry_meaning_id'], 'ss_items_meaning_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_session_items');
        Schema::dropIfExists('study_sessions');
        Schema::dropIfExists('study_records');
    }
};
