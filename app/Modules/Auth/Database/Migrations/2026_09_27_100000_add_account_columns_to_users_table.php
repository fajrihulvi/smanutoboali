<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('public_id')->nullable()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        DB::table('users')->whereNull('public_id')->orderBy('id')->each(function (object $user) {
            DB::table('users')->where('id', $user->id)->update(['public_id' => (string) Str::ulid()]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('public_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['public_id', 'is_active', 'last_login_at']);
        });
    }
};
