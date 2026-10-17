<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->string('location')->nullable();
            $table->string('country', 80)->nullable()->default('Ghana');
            $table->unsignedSmallInteger('years_experience')->default(0);
            $table->string('education_level', 30)->nullable();
            $table->string('field_of_study')->nullable();
            $table->string('desired_title')->nullable();
            $table->string('desired_job_type', 30)->nullable();
            $table->string('desired_location')->nullable();
            $table->unsignedInteger('expected_salary')->nullable();
            $table->string('availability', 30)->nullable();
            $table->text('bio')->nullable();

            // Study-abroad interest, captured as a lead as well.
            $table->string('interest_type', 20)->default('job')->index();
            $table->string('study_destination', 80)->nullable();
            $table->string('study_level', 30)->nullable();
            $table->string('study_intake', 30)->nullable();

            $table->boolean('consent_marketing')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->unsignedTinyInteger('completeness')->default(0);
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('candidate_skill', function (Blueprint $table) {
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['candidate_profile_id', 'skill_id']);
        });

        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cvs');
        Schema::dropIfExists('candidate_skill');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('candidate_profiles');
    }
};
