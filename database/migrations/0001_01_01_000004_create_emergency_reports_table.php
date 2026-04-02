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
        Schema::create('emergency_reports', function (Blueprint $table) {
            $table->id();

            // User who reported (nullable if anonymous)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Type of emergency
            $table->string('type');
            $table->foreign('type')
                  ->references('code')
                  ->on('emergency_types')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            // Additional report details
            $table->string('contact_name')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();

            // Location data
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('landmark')->nullable();

            // Severity and priority
            $table->enum('severity_level', [
                'low',
                'moderate',
                'high',
                'critical'
            ])->default('moderate');

            // Response tracking
            $table->enum('status', [
                'pending',
                'acknowledged',
                'dispatched',
                'in_progress',
                'resolved',
                'cancelled'
            ])->default('pending');

            // Assigned responder or department
            $table->foreignId('assigned_responder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('responding_unit')->nullable(); // e.g., Fire Dept, Police Station, etc.
            $table->string('response_notes')->nullable();

            // Timeline fields
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // System timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_reports');
    }
};
