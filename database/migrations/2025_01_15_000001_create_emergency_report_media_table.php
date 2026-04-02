<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emergency_report_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_report_id')->constrained('emergency_reports')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // image, video, audio, document
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('thumbnail_path')->nullable(); // for videos/images
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_report_media');
    }
};

