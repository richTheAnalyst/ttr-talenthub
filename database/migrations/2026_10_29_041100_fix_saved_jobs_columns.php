<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Repairs the saved_jobs table whatever shape an earlier attempt left it in. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saved_jobs')) {
            Schema::create('saved_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'job_posting_id']);
            });

            return;
        }

        // An earlier table may have called the column job_id.
        if (Schema::hasColumn('saved_jobs', 'job_id') && ! Schema::hasColumn('saved_jobs', 'job_posting_id')) {
            Schema::table('saved_jobs', fn (Blueprint $table) => $table->renameColumn('job_id', 'job_posting_id'));
        }

        Schema::table('saved_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('saved_jobs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('saved_jobs', 'job_posting_id')) {
                $table->foreignId('job_posting_id')->nullable()->constrained('job_postings')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('saved_jobs', 'created_at')) {
                $table->timestamps();
            }
        });

        if (! Schema::hasIndex('saved_jobs', ['user_id', 'job_posting_id'], 'unique')) {
            Schema::table('saved_jobs', fn (Blueprint $table) => $table->unique(['user_id', 'job_posting_id']));
        }
    }

    public function down(): void
    {
        // Intentionally empty: this only repairs an existing table.
    }
};