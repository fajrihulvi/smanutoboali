<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thumbnail gambar (S33) untuk daftar, kartu, dan galeri. Null = pakai file aslinya
 * (gambar sudah kecil atau bukan gambar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('thumbnail_path');
        });
    }
};
