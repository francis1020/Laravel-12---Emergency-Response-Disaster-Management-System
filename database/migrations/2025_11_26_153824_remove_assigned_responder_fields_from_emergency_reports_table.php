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
            $table->dropForeign(['assigned_responder_id']);
            $table->dropColumn(['assigned_responder_id', 'responding_unit', 'response_notes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_reports', function (Blueprint $table) {
            $table->foreignId('assigned_responder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('responding_unit')->nullable();
            $table->text('response_notes')->nullable();
        });
    }
};
