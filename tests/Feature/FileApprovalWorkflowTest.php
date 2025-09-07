<?php

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users with different roles
    $this->user = User::factory()->create(['role' => 'user']);
    $this->checker = User::factory()->create(['role' => 'checker']);
    $this->registrator = User::factory()->create(['role' => 'registrator']);
    $this->ceo = User::factory()->create(['role' => 'ceo']);

    // Create a test file
    $this->file = UploadedFile::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);
});

describe('Role-based access control', function () {
    it('allows users to view their own files', function () {
        $response = $this->actingAs($this->user)->get('/approval/history');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('FileApproval/UserHistory'));
    });

    it('allows checkers to view pending files', function () {
        $response = $this->actingAs($this->checker)->get('/approval/pending');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('FileApproval/PendingFiles'));
    });

    it('allows registrators to view waiting files', function () {
        $response = $this->actingAs($this->registrator)->get('/approval/waiting');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('FileApproval/WaitingFiles'));
    });

    it('allows CEOs to view analytics', function () {
        $response = $this->actingAs($this->ceo)->get('/approval/analytics');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('FileApproval/Analytics'));
    });

    it('prevents users from accessing checker pages', function () {
        $response = $this->actingAs($this->user)->get('/approval/pending');

        $response->assertForbidden();
    });

    it('prevents users from accessing registrator pages', function () {
        $response = $this->actingAs($this->user)->get('/approval/waiting');

        $response->assertForbidden();
    });

    it('prevents users from accessing CEO pages', function () {
        $response = $this->actingAs($this->user)->get('/approval/analytics');

        $response->assertForbidden();
    });
});

describe('File approval workflow', function () {
    it('allows checkers to approve pending files', function () {
        $response = $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/approve-checker");

        $response->assertSuccessful();
        $response->assertJson(['status' => 'waiting']);

        $this->file->refresh();
        expect($this->file->status)->toBe('waiting');
    });

    it('allows registrators to approve waiting files', function () {
        // First, make the file waiting (approved by checker)
        $this->file->update(['status' => 'waiting']);

        $response = $this->actingAs($this->registrator)
            ->postJson("/approval/files/{$this->file->id}/approve-registrator");

        $response->assertSuccessful();
        $response->assertJson(['status' => 'accepted']);

        $this->file->refresh();
        expect($this->file->status)->toBe('accepted');
    });

    it('allows checkers to reject pending files', function () {
        $response = $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/reject", [
                'notes' => 'File does not meet requirements',
            ]);

        $response->assertSuccessful();
        $response->assertJson(['status' => 'rejected']);

        $this->file->refresh();
        expect($this->file->status)->toBe('rejected');
        expect($this->file->admin_notes)->toBe('File does not meet requirements');
    });

    it('allows registrators to reject waiting files', function () {
        // First, make the file waiting (approved by checker)
        $this->file->update(['status' => 'waiting']);

        $response = $this->actingAs($this->registrator)
            ->postJson("/approval/files/{$this->file->id}/reject", [
                'notes' => 'Final review failed',
            ]);

        $response->assertSuccessful();
        $response->assertJson(['status' => 'rejected']);

        $this->file->refresh();
        expect($this->file->status)->toBe('rejected');
        expect($this->file->admin_notes)->toBe('Final review failed');
    });

    it('prevents checkers from approving non-pending files', function () {
        // Make file waiting
        $this->file->update(['status' => 'waiting']);

        $response = $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/approve-checker");

        $response->assertStatus(403);
    });

    it('prevents registrators from approving non-waiting files', function () {
        $response = $this->actingAs($this->registrator)
            ->postJson("/approval/files/{$this->file->id}/approve-registrator");

        $response->assertStatus(403);
    });
});

describe('Analytics dashboard', function () {
    it('shows correct file counts for CEO', function () {
        // Create files with different statuses
        UploadedFile::factory()->create(['status' => 'accepted']);
        UploadedFile::factory()->create(['status' => 'accepted']);
        UploadedFile::factory()->create(['status' => 'rejected']);
        UploadedFile::factory()->create(['status' => 'pending']);
        UploadedFile::factory()->create(['status' => 'waiting']);

        $response = $this->actingAs($this->ceo)->get('/approval/analytics');

        $response->assertSuccessful();
        $response->assertInertia(
            fn ($page) => $page->component('FileApproval/Analytics')
                ->where('stats.accepted', 2)
                ->where('stats.rejected', 1)
                ->where('stats.pending', 2)
                ->where('stats.waiting', 1)
                ->where('stats.total', 6)
        );
    });
});

describe('File status transitions', function () {
    it('maintains proper workflow state', function () {
        // File starts as pending
        expect($this->file->status)->toBe('pending');

        // Checker approves -> waiting
        $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/approve-checker");

        $this->file->refresh();
        expect($this->file->status)->toBe('waiting');

        // Registrator approves -> accepted
        $this->actingAs($this->registrator)
            ->postJson("/approval/files/{$this->file->id}/approve-registrator");

        $this->file->refresh();
        expect($this->file->status)->toBe('accepted');
    });

    it('allows rejection at any workflow stage', function () {
        // Reject while pending
        $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/reject", [
                'notes' => 'Rejected at checker stage',
            ]);

        $this->file->refresh();
        expect($this->file->status)->toBe('rejected');

        // Reset and test rejection at waiting stage
        $this->file->update(['status' => 'pending']);
        $this->actingAs($this->checker)
            ->postJson("/approval/files/{$this->file->id}/approve-checker");

        $this->file->refresh();
        expect($this->file->status)->toBe('waiting');

        $this->actingAs($this->registrator)
            ->postJson("/approval/files/{$this->file->id}/reject", [
                'notes' => 'Rejected at registrator stage',
            ]);

        $this->file->refresh();
        expect($this->file->status)->toBe('rejected');
    });
});
