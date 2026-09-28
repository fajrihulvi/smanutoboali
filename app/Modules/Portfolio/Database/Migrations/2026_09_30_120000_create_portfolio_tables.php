<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('portfolio_categories')->insert(array_map(
            fn (array $row, int $i) => ['code' => $row[0], 'name' => $row[1], 'position' => $i + 1, 'created_at' => $now, 'updated_at' => $now],
            [['KARYA', 'Karya'], ['TUGAS', 'Tugas'], ['PRESTASI', 'Prestasi'], ['SERTIFIKAT', 'Sertifikat'], ['PROYEK', 'Proyek'], ['DOKUMENTASI', 'Dokumentasi']],
            array_keys(range(0, 5)),
        ));

        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained('portfolio_categories')->restrictOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->date('date');
            $table->string('visibility', 10)->default('DRAFT');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'date']);
        });

        Schema::create('portfolio_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->unique(['portfolio_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_student');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('portfolio_categories');
    }
};
