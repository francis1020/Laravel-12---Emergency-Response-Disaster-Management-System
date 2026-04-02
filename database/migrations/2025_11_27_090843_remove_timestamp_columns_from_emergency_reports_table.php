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
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->dropColumn([
                'acknowledged_at',
                'dispatched_at',
                'in_progress_at',
                'resolved_at',
                'cancelled_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });
    }
};
