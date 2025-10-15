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
        Schema::create('subscription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Назва: "2 заняття на тиждень", "3 заняття", "Груповий"
            $table->enum('type', ['individual', 'group']); // Тип абонементу
            $table->integer('lessons_per_week'); // Кількість занять на тиждень
            $table->decimal('price', 8, 2); // Ціна за місяць
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_templates');
    }
};
