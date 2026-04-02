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
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'acknowledged',
                'dispatched',
                'in_progress',
                'resolved',
                'cancelled'
            ])->default('pending')->after('severity_level');
        });
    }
};
