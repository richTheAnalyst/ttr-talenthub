<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiring_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 30)->nullable();
            $table->string('role_title');
            $table->unsignedSmallInteger('headcount')->default(1);
            $table->string('location')->nullable();
            $table->string('job_type', 30)->nullable();
            $table->text('required_skills')->nullable();
            $table->text('description')->nullable();
            $table->string('urgency', 20)->default('normal');
            $table->string('budget')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('source', 30)->default('web');
            $table->string('stage', 20)->default('new')->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->jsonb('details')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('crm_synced_at')->nullable();
            $table->string('crm_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keywords')->nullable();
            $table->string('location')->nullable();
            $table->string('job_type', 30)->nullable();
            $table->unsignedTinyInteger('min_match')->default(50);
            $table->string('frequency', 10)->default('instant');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('hiring_requests');
    }
};
