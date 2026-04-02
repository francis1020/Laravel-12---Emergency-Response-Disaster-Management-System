<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responder_details', function (Blueprint $table) {
            $table->id();
            
            // Link to the users table (responder is a user)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Responder details
            $table->string('department')->nullable();
            $table->string('station_address')->nullable(); // Changed from address
            $table->string('vehicle_type')->nullable();    // e.g., ambulance, fire truck
            $table->string('license_number')->nullable();
            $table->string('status')->default('available'); // available, busy, offline

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responder_details');
    }
};
