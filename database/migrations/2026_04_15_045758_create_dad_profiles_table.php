<?php
// database/migrations/2026_04_15_000001_create_dad_profiles_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dad_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'expectant' or 'existing'
            $table->boolean('track_mom')->default(false);
            $table->string('partner_name')->nullable();
            $table->json('tab_config')->nullable(); // UI modules e.g. ["sleep", "feeds"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dad_profiles');
    }
};