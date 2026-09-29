<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->string('selection_note', 255)->nullable()->after('score');
            $table->string('proposal', 10)->nullable()->after('selection_note');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
            $table->timestamp('result_notified_at')->nullable()->after('decided_by');
            $table->timestamp('reregistration_submitted_at')->nullable()->after('result_notified_at');
            $table->timestamp('reregistered_at')->nullable()->after('reregistration_submitted_at');
            $table->foreignId('reregistered_by')->nullable()->after('reregistered_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->timestamp('results_published_at')->nullable()->after('announces_at');
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_periods', fn (Blueprint $table) => $table->dropColumn('results_published_at'));
        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropConstrainedForeignId('reregistered_by');
            $table->dropColumn(['selection_note', 'proposal', 'result_notified_at', 'reregistration_submitted_at', 'reregistered_at']);
        });
    }
};
