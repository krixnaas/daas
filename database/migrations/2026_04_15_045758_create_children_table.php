<?php
// database/migrations/2026_04_15_000002_create_children_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dad_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
              $table->date('due_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('born'); 
            $table->float('weight')->nullable(); // in kg
            $table->float('height')->nullable(); // in cm
            $table->float('head_circumference')->nullable(); // in cm
            $table->string('blood_group')->nullable();
            $table->date('umbilical_cord_fell_off_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};