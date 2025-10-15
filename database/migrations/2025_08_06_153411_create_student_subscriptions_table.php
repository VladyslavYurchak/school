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
        Schema::create('student_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            // nullable, бо для поразової оплати шаблон не потрібен
            $table->foreignId('subscription_template_id')->nullable()->constrained()->onDelete('cascade');
            $table->unsignedInteger('price');

            // Тип оплати: 'subscription' або 'single'
            $table->enum('type', ['subscription', 'single'])->default('subscription');

            // Для абонементу — місяць, для поразової — дата уроку
            $table->date('start_date'); // для subscription: 1 число місяця, для single — дата уроку
            $table->date('end_date');   // для subscription: останнє число місяця, для single — теж дата уроку

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subscriptions');
    }
};
