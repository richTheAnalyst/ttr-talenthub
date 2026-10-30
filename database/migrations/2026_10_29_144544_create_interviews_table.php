<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interviews')) {
            return;
        }

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('mode', 20)->default('video');
            $table->string('details', 500)->nullable();   // meeting link, phone number or address
            $table->text('note')->nullable();             // message from the employer
            $table->string('status', 20)->default('proposed'); // proposed, confirmed, declined, cancelled
            $table->string('decline_reason', 500)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};