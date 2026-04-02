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
        Schema::table('responder_details', function (Blueprint $table) {
            $table->decimal('office_location_latitude', 10, 7)->nullable()->after('station_address');
            $table->decimal('office_location_longitude', 10, 7)->nullable()->after('office_location_latitude');
            $table->boolean('auto_assign')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responder_details', function (Blueprint $table) {
            $table->dropColumn(['office_location_latitude', 'office_location_longitude', 'auto_assign']);
        });
    }
};
