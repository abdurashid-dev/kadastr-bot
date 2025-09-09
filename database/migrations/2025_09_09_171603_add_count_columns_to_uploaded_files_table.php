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
        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->integer('registered_count')->default(0)->after('admin_notes');
            $table->integer('not_registered_count')->default(0)->after('registered_count');
            $table->text('accepted_note')->nullable()->after('not_registered_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->dropColumn(['registered_count', 'not_registered_count', 'accepted_note']);
        });
    }
};
