<?php
// database/migrations/2026_04_15_000004_create_activity_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dad_profile_id')->constrained()->cascadeOnDelete();
            
            // This allows the log to belong to a Child OR a Pregnancy
            $table->nullableMorphs('subject'); 
            
            $table->string('category'); // e.g., 'sleep', 'feed', 'meds', 'kick_count'
            $table->json('data');       // Flexible storage for specific log details
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};