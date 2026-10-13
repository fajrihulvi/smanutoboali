<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Profil sekolah tunggal (satu instalasi = satu sekolah). Identitas yang
 * sebelumnya disimpan di `settings` (S07) dipindahkan ke sini.
 */
return new class extends Migration
{
    private const LEGACY_KEYS = ['school_name', 'school_logo', 'school_address', 'school_phone', 'school_email'];

    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('npsn', 8)->nullable();
            $table->string('level', 5);
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->foreignId('logo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('principal_name', 150)->nullable();
            $table->string('principal_nip', 30)->nullable();
            $table->timestamps();
        });

        $legacy = DB::table('settings')->whereIn('key', self::LEGACY_KEYS)->pluck('value', 'key')
            ->map(fn (?string $value) => $value === null ? null : json_decode($value, true));

        $logoFileId = $legacy->get('school_logo')
            ? DB::table('files')->where('public_id', $legacy->get('school_logo'))->value('id')
            : null;

        DB::table('schools')->insert([
            'name' => $legacy->get('school_name') ?: 'SMA NU Toboali',
            'level' => 'SMP',
            'address' => $legacy->get('school_address'),
            'phone' => $legacy->get('school_phone'),
            'email' => $legacy->get('school_email'),
            'logo_file_id' => $logoFileId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->whereIn('key', self::LEGACY_KEYS)->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
