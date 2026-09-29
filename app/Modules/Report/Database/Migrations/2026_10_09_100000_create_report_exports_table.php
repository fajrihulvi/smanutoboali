<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('report', 40);
            $table->string('format', 10);
            $table->json('filters');
            $table->string('title');
            $table->string('status', 20)->default('PENDING');
            $table->unsignedInteger('rows')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
