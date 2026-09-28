<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_periods', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 100);
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->dateTime('announces_at')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'opens_at', 'closes_at']);
        });

        Schema::create('ppdb_tracks', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('ppdb_period_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('quota');
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ppdb_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('ppdb_track_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ppdb_applicants', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            // Diberikan saat pertama kali dikirim.
            $table->string('registration_number', 20)->nullable()->unique();
            $table->foreignId('ppdb_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('ppdb_track_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('DRAFT');
            // Data calon siswa (kolom setara `students` + NIK & asal sekolah).
            $table->string('name', 150)->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('nisn', 10)->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->text('revision_note')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ppdb_period_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('ppdb_applicant_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppdb_applicant_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 5);
            $table->string('name', 150);
            $table->string('nik', 16)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['ppdb_applicant_id', 'relationship']);
        });

        Schema::create('ppdb_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('ppdb_applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ppdb_document_requirement_id')->constrained()->restrictOnDelete();
            $table->string('status', 10)->default('PENDING');
            $table->string('note', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['ppdb_applicant_id', 'ppdb_document_requirement_id'], 'ppdb_documents_applicant_requirement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_documents');
        Schema::dropIfExists('ppdb_applicant_guardians');
        Schema::dropIfExists('ppdb_applicants');
        Schema::dropIfExists('ppdb_document_requirements');
        Schema::dropIfExists('ppdb_tracks');
        Schema::dropIfExists('ppdb_periods');
    }
};
