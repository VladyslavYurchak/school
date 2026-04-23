<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testing_leads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('testing_sessions')->cascadeOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('telegram')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('preferred_study_format')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('contact_consent')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_leads');
    }
};
