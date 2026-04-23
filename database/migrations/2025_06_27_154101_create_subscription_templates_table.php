<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['individual', 'group', 'pair']);
            $table->unsignedInteger('lessons_per_week');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_templates');
    }
};
