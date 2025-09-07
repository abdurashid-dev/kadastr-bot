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
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL specific constraint handling
            DB::statement('ALTER TABLE uploaded_files DROP CONSTRAINT IF EXISTS uploaded_files_status_check');
            DB::statement("UPDATE uploaded_files SET status = 'accepted' WHERE status = 'approved'");
            DB::statement("UPDATE uploaded_files SET status = 'pending' WHERE status IS NULL");
            DB::statement("ALTER TABLE uploaded_files ADD CONSTRAINT uploaded_files_status_check CHECK (status IN ('pending', 'waiting', 'accepted', 'rejected'))");
        } else {
            // For SQLite and other databases, update the data and constraint
            DB::statement("UPDATE uploaded_files SET status = 'accepted' WHERE status = 'approved'");
            DB::statement("UPDATE uploaded_files SET status = 'pending' WHERE status IS NULL");

            // SQLite: Drop and recreate the table with new constraint
            if ($driver === 'sqlite') {
                // Get the table structure
                $columns = DB::select('PRAGMA table_info(uploaded_files)');
                $foreignKeys = DB::select('PRAGMA foreign_key_list(uploaded_files)');

                // Create new table with correct constraint
                DB::statement("CREATE TABLE uploaded_files_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id BIGINT NOT NULL,
                    name VARCHAR NOT NULL,
                    original_filename VARCHAR NOT NULL,
                    telegram_file_id VARCHAR NOT NULL,
                    file_path VARCHAR NOT NULL,
                    file_type VARCHAR NOT NULL,
                    mime_type VARCHAR,
                    file_size BIGINT,
                    status VARCHAR NOT NULL CHECK (status IN ('pending', 'waiting', 'accepted', 'rejected')) DEFAULT 'pending',
                    admin_notes TEXT,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )");

                // Copy data
                DB::statement('INSERT INTO uploaded_files_new SELECT * FROM uploaded_files');

                // Drop old table and rename new one
                DB::statement('DROP TABLE uploaded_files');
                DB::statement('ALTER TABLE uploaded_files_new RENAME TO uploaded_files');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE uploaded_files DROP CONSTRAINT IF EXISTS uploaded_files_status_check');
            DB::statement("UPDATE uploaded_files SET status = 'approved' WHERE status = 'accepted'");
            DB::statement("ALTER TABLE uploaded_files ADD CONSTRAINT uploaded_files_status_check CHECK (status IN ('pending', 'approved', 'rejected'))");
        } else {
            // For SQLite and other databases, just update the data
            DB::statement("UPDATE uploaded_files SET status = 'approved' WHERE status = 'accepted'");
        }
    }
};
