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
            $table->integer('priority_score')->default(0)->after('severity_level');
            $table->boolean('is_duplicate')->default(false)->after('priority_score');
            $table->foreignId('original_report_id')->nullable()->constrained('emergency_reports')->onDelete('set null')->after('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->dropForeign(['original_report_id']);
            $table->dropColumn(['priority_score', 'is_duplicate', 'original_report_id']);
        });
    }
};

