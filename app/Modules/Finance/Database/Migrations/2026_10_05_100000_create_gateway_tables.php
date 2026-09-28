<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 20)->default('midtrans');
            // Unik per percobaan bayar (Midtrans menolak order_id berulang).
            $table->string('order_id', 50)->unique();
            $table->unsignedInteger('amount');
            $table->string('status', 12)->default('CREATED');
            $table->string('snap_token', 100)->nullable();
            $table->string('redirect_url', 255)->nullable();
            $table->string('payment_type', 40)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });

        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20);
            $table->string('order_id', 50)->nullable()->index();
            $table->string('transaction_status', 30)->nullable();
            $table->string('status_code', 10)->nullable();
            $table->string('gross_amount', 30)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('result', 30)->nullable();
            $table->json('payload');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
        Schema::dropIfExists('gateway_transactions');
    }
};
