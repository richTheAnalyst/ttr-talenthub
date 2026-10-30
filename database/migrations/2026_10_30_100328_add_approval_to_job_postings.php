<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_postings', 'approval_status')) {
            Schema::table('job_postings', function (Blueprint $table) {
                // Existing jobs stay live: they default to "approved".
                $table->string('approval_status', 20)->default('approved')->index();
                $table->string('approval_note', 500)->nullable();
                $table->timestamp('approved_at')->nullable();
            });

            DB::table('job_postings')->update(['approved_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approval_note', 'approved_at']);
        });
    }
};