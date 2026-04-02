<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // must be same type and length as in reports
            $table->string('name');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        // Optional: Insert default emergency types
        DB::table('emergency_types')->insert([
            [
                'code' => 'fire',
                'name' => 'Fire',
                'icon' => 'bi bi-fire',
                'description' => 'Fire-related emergencies',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'road_accident',
                'name' => 'Road Accident',
                'icon' => 'bi bi-car-front',
                'description' => 'Vehicular accidents and collisions',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'police_assistance',
                'name' => 'Police Assistance',
                'icon' => 'bi bi-shield-lock',
                'description' => 'Criminal or security-related emergencies',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'medical_emergency',
                'name' => 'Medical Emergency',
                'icon' => 'bi bi-heart-pulse',
                'description' => 'Health and injury-related emergencies',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'rescue',
                'name' => 'Rescue Operation',
                'icon' => 'bi bi-life-preserver',
                'description' => 'Rescue of trapped or endangered persons',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'flood',
                'name' => 'Flood',
                'icon' => 'bi bi-droplet',
                'description' => 'Flooding incidents',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'earthquake',
                'name' => 'Earthquake',
                'icon' => 'bi bi-houses',
                'description' => 'Earthquake-related emergencies',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'others',
                'name' => 'Others',
                'icon' => 'bi bi-exclamation-triangle',
                'description' => 'Other types of emergencies',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_types');
    }
};
