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
        Schema::create('decks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('learning_language_id')->constrained('languages')->restrictOnDelete();
            $table->foreignId('meaning_language_id')->constrained('languages')->restrictOnDelete();
            $table->string('visibility', 20)->default('private');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['visibility', 'created_at']);
            $table->index('learning_language_id');
            $table->index('meaning_language_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decks');
    }
};

