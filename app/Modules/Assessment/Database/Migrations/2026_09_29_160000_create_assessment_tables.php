<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_components', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('name', 50);
            $table->unsignedTinyInteger('weight');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teaching_assignment_id', 'semester_id']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->string('note', 255)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['grade_component_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('grade_description_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->char('predicate', 1);
            $table->text('template');
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'predicate'], 'grade_templates_unique');
        });

        Schema::create('grade_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['teaching_assignment_id', 'semester_id', 'student_id'], 'grade_descriptions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_descriptions');
        Schema::dropIfExists('grade_description_templates');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('grade_components');
    }
};
