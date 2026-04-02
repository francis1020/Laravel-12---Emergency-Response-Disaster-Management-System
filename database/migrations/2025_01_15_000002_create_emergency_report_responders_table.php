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
        Schema::create('emergency_report_responders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_report_id')->constrained('emergency_reports')->onDelete('cascade');
            $table->foreignId('responder_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['primary', 'secondary', 'support'])->default('secondary');
            $table->enum('status', ['assigned', 'en_route', 'on_scene', 'completed', 'cancelled'])->default('assigned');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('on_scene_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate assignments (using shorter name for MySQL compatibility)
            $table->unique(['emergency_report_id', 'responder_id'], 'er_responders_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_report_responders');
    }
};

