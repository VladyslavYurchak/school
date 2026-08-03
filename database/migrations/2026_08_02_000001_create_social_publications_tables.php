<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('caption')->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_type', 20)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('last_published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('social_publication_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_publication_id')
                ->constrained('social_publications')
                ->cascadeOnDelete();
            $table->string('platform', 20);
            $table->string('status', 30)->default('pending');
            $table->string('provider_post_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->unique(['social_publication_id', 'platform'], 'social_publication_platform_unique');
            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_publication_targets');
        Schema::dropIfExists('social_publications');
    }
};
