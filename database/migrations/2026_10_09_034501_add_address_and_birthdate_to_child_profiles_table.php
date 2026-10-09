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
        Schema::table('child_profiles', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('age');
            $table->string('address')->nullable()->after('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('child_profiles', function (Blueprint $table) {
            $table->dropColumn(['birthdate', 'address']);
        });
    }
};
