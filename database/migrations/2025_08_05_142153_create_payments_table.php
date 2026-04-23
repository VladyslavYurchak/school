<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('UAH');

            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])
                ->default('pending');

            $table->enum('type', ['subscription', 'single', 'balance'])
                ->default('subscription');

            $table->string('provider')->nullable(); // liqpay, fondy, wayforpay
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_order_id')->nullable()->index();

            $table->text('description')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
