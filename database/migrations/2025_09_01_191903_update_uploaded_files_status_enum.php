<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL specific constraint handling
            DB::statement("ALTER TABLE uploaded_files DROP CONSTRAINT IF EXISTS uploaded_files_status_check");
            DB::statement("UPDATE uploaded_files SET status = 'accepted' WHERE status = 'approved'");
            DB::statement("UPDATE uploaded_files SET status = 'pending' WHERE status IS NULL");
            DB::statement("ALTER TABLE uploaded_files ADD CONSTRAINT uploaded_files_status_check CHECK (status IN ('pending', 'waiting', 'accepted', 'rejected'))");
        } else {
            // For SQLite and other databases, just update the data
            // SQLite doesn't enforce check constraints the same way
            DB::statement("UPDATE uploaded_files SET status = 'accepted' WHERE status = 'approved'");
            DB::statement("UPDATE uploaded_files SET status = 'pending' WHERE status IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE uploaded_files DROP CONSTRAINT IF EXISTS uploaded_files_status_check");
            DB::statement("UPDATE uploaded_files SET status = 'approved' WHERE status = 'accepted'");
            DB::statement("ALTER TABLE uploaded_files ADD CONSTRAINT uploaded_files_status_check CHECK (status IN ('pending', 'approved', 'rejected'))");
        } else {
            // For SQLite and other databases, just update the data
            DB::statement("UPDATE uploaded_files SET status = 'approved' WHERE status = 'accepted'");
        }
    }
};
