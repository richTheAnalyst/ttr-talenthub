<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair migration: creates anything earlier migrations should have made but did not
 * (the live database was missing the settings table). Safe to run more than once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('endpoint_hash', 40)->unique();
                $table->text('endpoint');
                $table->string('public_key');
                $table->string('auth_token');
                $table->string('content_encoding', 20)->default('aes128gcm');
                $table->string('user_agent', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('cvs', 'review_status')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->string('review_status', 20)->nullable()->index();
            });
        }

        if (! Schema::hasColumn('cvs', 'admin_notes')) {
            Schema::table('cvs', function (Blueprint $table) {
                $table->text('admin_notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: this only repairs missing structure.
    }
};
