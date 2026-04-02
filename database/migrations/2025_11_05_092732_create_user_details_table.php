<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            
            // Link to the users table
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Additional normal user info
            $table->string('contact_number', 11)->nullable();
            $table->string('address')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable(); // e.g., male, female, other
            $table->string('profile_picture')->nullable(); // optional

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
