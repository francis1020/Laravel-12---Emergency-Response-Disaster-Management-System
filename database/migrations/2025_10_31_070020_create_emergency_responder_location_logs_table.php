<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_responder_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('emergency_reports')->onDelete('cascade');
            $table->foreignId('responder_id')->constrained('users')->onDelete('cascade');
            $table->string('responder_name');
            $table->text('message')->nullable();
            $table->text('summary')->nullable();
            $table->text('severity_level')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_responder_location_logs');
    }
};


