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
        Schema::table('emergencies', function (Blueprint $table) {
            if (! Schema::hasColumn('emergencies', 'reported_by_id')) {
                $table->foreignId('reported_by_id')
                    ->nullable()
                    ->after('guardian_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('emergencies', 'reported_by_role')) {
                $table->string('reported_by_role')
                    ->nullable()
                    ->after('reported_by_id');
            }

            if (! Schema::hasColumn('emergencies', 'facility_room')) {
                $table->string('facility_room')
                    ->nullable()
                    ->after('location');
            }

            if (! Schema::hasColumn('emergencies', 'responder_arrived_at')) {
                $table->timestamp('responder_arrived_at')
                    ->nullable()
                    ->after('dispatched_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('emergencies', 'responder_arrived_at')) {
                $table->dropColumn('responder_arrived_at');
            }

            if (Schema::hasColumn('emergencies', 'facility_room')) {
                $table->dropColumn('facility_room');
            }

            if (Schema::hasColumn('emergencies', 'reported_by_role')) {
                $table->dropColumn('reported_by_role');
            }

            if (Schema::hasColumn('emergencies', 'reported_by_id')) {
                $table->dropForeign(['reported_by_id']);
                $table->dropColumn('reported_by_id');
            }
        });
    }
};
