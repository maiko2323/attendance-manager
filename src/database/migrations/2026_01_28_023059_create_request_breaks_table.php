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
        Schema::create('request_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_request_id')
                    ->constrained('attendance_requests')
                    ->cascadeOnDelete();
            $table->tinyInteger('break_no');
            $table->time('break_start_at');
            $table->time('break_end_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_breaks');
    }
};
