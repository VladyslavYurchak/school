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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();

            $table->decimal('custom_lesson_price', 8, 2)->nullable();
            $table->decimal('custom_group_lesson_price', 8, 2)->nullable();

            $table->integer('remaining_lessons')->default(0);
            $table->integer('remaining_group_lessons')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('parent_contact')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('total_lessons_attended')->default(0);
            $table->decimal('balance', 10, 2)->default(0)->comment('Залишок коштів на рахунку учня');
            $table->text('note')->nullable();

            // 🔽 Додано поле для шаблону абонементу
            $table->unsignedBigInteger('subscription_id')->nullable();

            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('teachers')->nullOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscription_templates')->nullOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
