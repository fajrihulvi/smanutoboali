<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks tambahan hasil audit performa (S33).
 *
 * - attendance_sessions.date: rekap & tren kehadiran sekolah memfilter rentang tanggal
 *   lintas rombel/tahun (indeks lama diawali academic_year_id / classroom_id).
 * - notifications (notifiable, read_at): hitung notifikasi belum dibaca di setiap request.
 * - website_pages / website_galleries (status, publish_at): scope `live()` website publik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->index('date');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
        });

        Schema::table('website_pages', function (Blueprint $table) {
            $table->index(['status', 'publish_at']);
        });

        Schema::table('website_galleries', function (Blueprint $table) {
            $table->index(['status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', fn (Blueprint $table) => $table->dropIndex(['date']));
        Schema::table('notifications', fn (Blueprint $table) => $table->dropIndex('notifications_unread_index'));
        Schema::table('website_pages', fn (Blueprint $table) => $table->dropIndex(['status', 'publish_at']));
        Schema::table('website_galleries', fn (Blueprint $table) => $table->dropIndex(['status', 'publish_at']));
    }
};
