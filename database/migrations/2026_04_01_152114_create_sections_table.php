<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('testing_tests')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 50)->default('mixed');
            $table->text('instruction_text')->nullable();

            $table->string('media_type', 50)->default('none');
            $table->string('media_url')->nullable();
            $table->string('media_title')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['test_id', 'sort_order']);
            $table->index(['test_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_sections');
    }
};
