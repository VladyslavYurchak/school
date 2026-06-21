<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_name')->nullable();
            $table->string('media_mime')->nullable();
            $table->unsignedBigInteger('media_size')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_content_blocks');
    }
};
