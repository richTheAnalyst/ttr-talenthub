<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('size', 30)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('country', 80)->nullable()->default('Ghana');
            $table->string('job_type', 30)->default('full_time');
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('currency', 3)->default('GHS');
            $table->unsignedSmallInteger('experience_min')->default(0);
            $table->string('education_level', 30)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_skill', function (Blueprint $table) {
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->primary(['job_posting_id', 'skill_id']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->nullable()->constrained('cvs')->nullOnDelete();
            $table->text('cover_note')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['job_posting_id', 'user_id']);
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('job_skill');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('employers');
    }
};
