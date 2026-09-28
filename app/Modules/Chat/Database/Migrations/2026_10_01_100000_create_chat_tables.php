<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('type', 10)->default('DIRECT');
            // "minUserId:maxUserId:studentId|0" — satu percakapan per pasangan per konteks anak.
            $table->string('direct_key', 64)->nullable()->unique();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->boolean('muted')->default(false);
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('type', 10)->default('TEXT');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
