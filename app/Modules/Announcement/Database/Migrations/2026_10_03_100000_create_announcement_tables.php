<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->string('audience', 10)->default('SEMUA');
            $table->json('roles')->nullable();
            $table->boolean('pinned')->default(false);
            // null = draf; masa depan = terjadwal; sudah lewat = terbit.
            $table->timestamp('publish_at')->nullable();
            // Notifikasi audiens hanya sekali.
            $table->timestamp('notified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publish_at', 'pinned']);
        });

        Schema::create('announcement_classroom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();

            $table->unique(['announcement_id', 'classroom_id']);
            $table->index('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_classroom');
        Schema::dropIfExists('announcements');
    }
};
