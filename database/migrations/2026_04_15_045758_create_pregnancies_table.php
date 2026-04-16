<?php
// database/migrations/2026_04_15_000003_create_pregnancies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dad_profile_id')->constrained()->cascadeOnDelete();
            $table->string('partner_name')->nullable();
            $table->date('due_date');
            $table->string('type')->default('singleton'); // singleton, twins, etc.
            $table->string('baby_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pregnancies');
    }
};