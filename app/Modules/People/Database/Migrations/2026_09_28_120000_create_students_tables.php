<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('nis', 20)->unique();
            $table->string('nisn', 10)->nullable()->unique();
            $table->string('name', 150);
            $table->char('gender', 1);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 10)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('photo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('status', 10)->default('AKTIF')->index();
            $table->date('entry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 5);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
            $table->index(['student_id', 'is_primary']);
        });

        Schema::create('student_imports', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('original_name');
            $table->string('status', 12)->default('PENDING');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->text('failure_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_imports');
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('students');
    }
};
