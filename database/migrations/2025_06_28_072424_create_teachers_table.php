<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            // Email (опціонально)
            $table->decimal('lesson_price', 8, 2)->nullable();
            $table->decimal('group_lesson_price', 8, 2)->nullable();    // Ставка за одне заняття
            $table->decimal('pair_lesson_price', 8, 2)->nullable();   // Ціна за парне заняття
            $table->decimal('trial_lesson_price', 8, 2)->nullable();  // Ціна за пробне заняття

// Ставка за одне заняття
            $table->text('note')->nullable();                     // Додаткові нотатки
            $table->boolean('is_active')->default(true);          // Активний/неактивний
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
