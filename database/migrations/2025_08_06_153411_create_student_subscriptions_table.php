<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subscription_template_id')
                ->nullable()
                ->constrained('subscription_templates')
                ->nullOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            $table->decimal('price', 10, 2);

            $table->enum('type', ['subscription', 'single'])
                ->default('subscription');

            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])
                ->default('pending');

            $table->date('start_date');
            $table->date('end_date');

            $table->unsignedInteger('lessons_total')->default(0);
            $table->unsignedInteger('lessons_used')->default(0);

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subscriptions');
    }
};
