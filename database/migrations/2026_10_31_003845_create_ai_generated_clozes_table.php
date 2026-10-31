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
        Schema::create('ai_generated_clozes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_meaning_id')->constrained('entry_meanings')->cascadeOnDelete();
            $table->foreignId('learning_language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->string('term', 255);
            $table->text('meaning');
            $table->text('sentence_full');
            $table->text('sentence_with_blank');
            $table->string('blank_answer', 255);
            $table->json('acceptable_variants')->nullable();
            $table->text('translation')->nullable();
            $table->text('explanation')->nullable();
            $table->json('distractors')->nullable();
            $table->timestamps();

            $table->index(['entry_meaning_id', 'created_at']);
            $table->index('term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generated_clozes');
    }
};
