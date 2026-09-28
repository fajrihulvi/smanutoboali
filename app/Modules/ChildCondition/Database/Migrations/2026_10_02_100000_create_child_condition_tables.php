<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_conditions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->text('description');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->foreignId('target_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('status', 15)->default('SUBMITTED');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['target_teacher_id', 'status']);
            $table->index(['student_id', 'valid_from']);
            $table->index('guardian_user_id');
        });

        Schema::create('child_condition_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_condition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_condition_responses');
        Schema::dropIfExists('child_conditions');
    }
};
