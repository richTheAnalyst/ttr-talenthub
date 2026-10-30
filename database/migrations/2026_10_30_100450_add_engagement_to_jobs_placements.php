<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_postings', 'engagement')) {
            Schema::table('job_postings', function (Blueprint $table) {
                // outsourcing: the client pays TTR, TTR pays the worker and keeps a percentage.
                // recruitment: TTR finds the person and charges the client a one-time fee.
                $table->string('engagement', 12)->default('recruitment');
                $table->unsignedInteger('client_budget')->nullable();   // monthly, outsourcing only
                $table->unsignedInteger('placement_fee')->nullable();   // fixed fee, recruitment only (set by admin)
                $table->decimal('fee_rate', 5, 2)->nullable();          // percentage taken at approval
            });
        }

        if (! Schema::hasColumn('placements', 'engagement')) {
            Schema::table('placements', function (Blueprint $table) {
                $table->string('engagement', 12)->default('outsourcing');
                $table->unsignedInteger('client_monthly')->nullable();  // what the client pays TTR each month
                $table->unsignedInteger('one_time_fee')->nullable();    // recruitment fee
                $table->timestamp('fee_paid_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_postings', fn (Blueprint $t) => $t->dropColumn(['engagement', 'client_budget', 'placement_fee', 'fee_rate']));
        Schema::table('placements', fn (Blueprint $t) => $t->dropColumn(['engagement', 'client_monthly', 'one_time_fee', 'fee_paid_at']));
    }
};