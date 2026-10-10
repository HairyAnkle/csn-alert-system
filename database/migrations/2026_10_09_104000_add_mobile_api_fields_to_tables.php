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
        // 1. Users table changes
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'unit')) {
                $table->string('unit')->nullable()->after('role');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });

        // 2. Child profiles table changes
        Schema::table('child_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('child_profiles', 'allergies')) {
                $table->text('allergies')->nullable()->after('condition');
            }
            if (! Schema::hasColumn('child_profiles', 'medications')) {
                $table->text('medications')->nullable()->after('allergies');
            }
            if (! Schema::hasColumn('child_profiles', 'communication_notes')) {
                $table->text('communication_notes')->nullable()->after('medications');
            }
            if (! Schema::hasColumn('child_profiles', 'guardian_phone')) {
                $table->string('guardian_phone')->nullable()->after('guardian_name');
            }
            if (! Schema::hasColumn('child_profiles', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('emergency_contact');
            }
            if (! Schema::hasColumn('child_profiles', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
        });

        // 3. Emergencies table changes
        Schema::table('emergencies', function (Blueprint $table) {
            if (! Schema::hasColumn('emergencies', 'guardian_id')) {
                $table->foreignId('guardian_id')->nullable()->after('child_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('emergencies', 'eta')) {
                $table->string('eta')->nullable()->after('responder_id');
            }
            if (! Schema::hasColumn('emergencies', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('resolved_at');
            }
        });

        // Make child_id nullable on emergencies (safety rule: never block an emergency because of missing data)
        try {
            Schema::table('emergencies', function (Blueprint $table) {
                $table->foreignId('child_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // Some drivers (older SQLite) may skip in-place nullability changes if already compatible
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('emergencies', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('emergencies', 'eta')) {
                $table->dropColumn('eta');
            }
            if (Schema::hasColumn('emergencies', 'guardian_id')) {
                $table->dropForeign(['guardian_id']);
                $table->dropColumn('guardian_id');
            }
        });

        Schema::table('child_profiles', function (Blueprint $table) {
            $cols = ['allergies', 'medications', 'communication_notes', 'guardian_phone', 'emergency_contact_name', 'emergency_contact_phone'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('child_profiles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};

