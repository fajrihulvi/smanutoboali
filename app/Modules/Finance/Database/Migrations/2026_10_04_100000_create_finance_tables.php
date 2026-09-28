<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 100);
            $table->unsignedInteger('default_amount');
            $table->string('frequency', 10);
            $table->boolean('is_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_discounts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            // null = berlaku untuk semua jenis biaya.
            $table->foreignId('fee_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 10);
            $table->unsignedInteger('value');
            $table->string('reason', 150);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'is_active']);
        });

        Schema::create('invoice_batches', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->string('period', 20);
            $table->string('period_label', 60);
            $table->unsignedInteger('amount');
            $table->date('due_date');
            $table->json('scope');
            $table->string('status', 10)->default('QUEUED');
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('number', 30)->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period', 20);
            $table->string('period_label', 60);
            // "studentId:feeTypeId:period" — dikosongkan saat dibatalkan agar bisa dibuat ulang.
            $table->string('dedupe_key', 80)->nullable()->unique();
            $table->date('due_date');
            $table->unsignedInteger('total');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->string('status', 10)->default('UNPAID');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
            $table->index(['student_id', 'status']);
            $table->index(['fee_type_id', 'period']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('description', 150);
            // Rupiah; baris potongan bernilai negatif.
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->string('method', 10);
            $table->string('status', 10);
            $table->unsignedInteger('amount');
            $table->date('paid_on');
            $table->string('receipt_number', 30)->nullable()->unique();
            $table->string('note', 255)->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'paid_on']);
        });

        Schema::create('finance_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_sequences');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_batches');
        Schema::dropIfExists('student_discounts');
        Schema::dropIfExists('fee_types');
    }
};
