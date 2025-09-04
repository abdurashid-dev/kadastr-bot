<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UploadedFile;

class UpdateExistingFilesStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing files with 'approved' status to 'accepted'
        UploadedFile::where('status', 'approved')->update(['status' => 'accepted']);

        // Update existing files with 'pending' status to remain 'pending'
        // Update existing files with 'rejected' status to remain 'rejected'

        // Set any files without a status to 'pending'
        UploadedFile::whereNull('status')->update(['status' => 'pending']);

        $this->command->info('Existing files status updated successfully!');
    }
}
