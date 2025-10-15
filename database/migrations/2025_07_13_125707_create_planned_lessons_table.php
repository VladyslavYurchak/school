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
        Schema::create('planned_lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('lesson_type', ['individual', 'group', 'pair', 'trial'])->default('individual');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('planned');
            $table->enum('initiator', ['student', 'teacher'])->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Індекси і зовнішні ключі
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_lessons');
    }
};
