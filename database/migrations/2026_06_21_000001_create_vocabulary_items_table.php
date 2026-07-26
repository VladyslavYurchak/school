<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vocabulary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->text('translation');
            $table->string('transcription')->nullable();
            $table->string('part_of_speech', 100)->nullable();
            $table->text('explanation')->nullable();
            $table->text('example')->nullable();
            $table->text('example_translation')->nullable();
            $table->string('audio_path')->nullable();
            $table->timestamps();

            $table->index(['language_id', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_items');
    }
};
