<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to update the check constraint
        if (DB::getDriverName() === 'pgsql') {
            // Drop the old check constraint
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            // Add new check constraint with all roles
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user', 'checker', 'registrator', 'ceo', 'branch_agency_head', 'branch_chamber_head', 'branch_deputy', 'onec_developer'))");
        } else {
            // For MySQL/MariaDB
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'checker', 'registrator', 'ceo', 'branch_agency_head', 'branch_chamber_head', 'branch_deputy', 'onec_developer') DEFAULT 'user'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop the new check constraint
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            // Restore the original check constraint
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user', 'checker', 'registrator', 'ceo'))");
        } else {
            // For MySQL, revert to original enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'checker', 'registrator', 'ceo') DEFAULT 'user'");
        }
    }
};
