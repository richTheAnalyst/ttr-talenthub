<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('hiring_requests', 'engagement')) {
                $table->string('engagement', 20)->default('recruitment')->after('urgency');
            }
            if (! Schema::hasColumn('hiring_requests', 'client_budget')) {
                $table->unsignedBigInteger('client_budget')->nullable()->after('budget');
            }
            if (! Schema::hasColumn('hiring_requests', 'placement_fee')) {
                $table->unsignedBigInteger('placement_fee')->nullable()->after('client_budget');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dropColumn(['engagement', 'client_budget', 'placement_fee']);
        });
    }
};