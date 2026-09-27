<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('disk', 20);
            $table->string('visibility', 10);
            $table->string('directory', 40);
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path')->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->nullableMorphs('fileable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['directory', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
