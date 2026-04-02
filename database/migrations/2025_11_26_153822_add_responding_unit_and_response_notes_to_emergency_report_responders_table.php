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
        Schema::table('emergency_report_responders', function (Blueprint $table) {
            $table->string('responding_unit')->nullable()->after('role');
            $table->text('response_notes')->nullable()->after('responding_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_report_responders', function (Blueprint $table) {
            $table->dropColumn(['responding_unit', 'response_notes']);
        });
    }
};
