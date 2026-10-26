<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_visits', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('full_name', 150);
            $table->string('institution', 150);
            $table->string('whatsapp', 20);
            $table->string('category', 25)->index();
            $table->text('purpose');
            $table->string('host', 25)->index();
            $table->foreignId('photo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('visited_at')->index();
            $table->string('source', 10)->default('WEBSITE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_visits');
    }
};
