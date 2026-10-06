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
        Schema::create('child_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('age')->nullable();
            $table->string('condition');
            $table->foreignId('guardian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guardian_name')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_profiles');
    }
};
