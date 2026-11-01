<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('placement_payments')) {
            return;
        }

        Schema::create('placement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->date('period');                              // first day of the month this payment covers
            $table->unsignedInteger('client_amount')->nullable(); // what the employer actually paid TTR
            $table->timestamp('client_paid_at')->nullable();
            $table->unsignedInteger('worker_amount')->nullable(); // what TTR actually paid the worker
            $table->timestamp('worker_paid_at')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();

            $table->unique(['placement_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_payments');
    }
};