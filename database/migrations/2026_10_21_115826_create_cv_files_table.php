<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** CV file contents, used when CV_DISK=database (hosts without persistent disk or object storage). */
    public function up(): void
    {
        Schema::create('cv_files', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->longText('data'); // base64 of the file
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_files');
    }
};