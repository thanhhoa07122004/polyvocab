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
        Schema::create('vocabulary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained('decks')->cascadeOnDelete();
            $table->string('term', 255);
            $table->string('pronunciation', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['deck_id', 'created_at']);
            $table->index('term');
        });

        Schema::create('entry_meanings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_entry_id')->constrained('vocabulary_entries')->cascadeOnDelete();
            $table->string('part_of_speech', 100)->nullable();
            $table->text('meaning');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['vocabulary_entry_id', 'sort_order']);
        });

        Schema::create('entry_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_meaning_id')->constrained('entry_meanings')->cascadeOnDelete();
            $table->text('sentence');
            $table->text('translation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['entry_meaning_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_examples');
        Schema::dropIfExists('entry_meanings');
        Schema::dropIfExists('vocabulary_entries');
    }
};

