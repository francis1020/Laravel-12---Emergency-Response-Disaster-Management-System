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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['vehicle', 'equipment', 'personnel', 'facility'])->default('equipment');
            $table->string('identifier')->nullable(); // License plate, serial number, etc.
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'in_use', 'maintenance', 'unavailable'])->default('available');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('current_report_id')->nullable()->constrained('emergency_reports')->onDelete('set null');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};

