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
        Schema::table('request_breaks', function (Blueprint $table) {
            $table->unique(['attendance_request_id', 'break_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_breaks', function (Blueprint $table) {
            $table->dropUnique(['attendance_request_id', 'break_no']);
        });
    }
};
