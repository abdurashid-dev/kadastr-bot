<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User Management', function () {
    beforeEach(function () {
        $this->ceo = User::factory()->ceo()->create();
        $this->registrator = User::factory()->registrator()->create();
        $this->checker = User::factory()->checker()->create();
        $this->user = User::factory()->create();
    });

    describe('User listing and access control', function () {
        it('allows CEOs to view all users', function () {
            $response = $this->actingAs($this->ceo)->get('/users');

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Index'));
        });

        it('allows registrators to view all users', function () {
            $response = $this->actingAs($this->registrator)->get('/users');

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Index'));
        });

        it('prevents checkers from viewing user management', function () {
            $response = $this->actingAs($this->checker)->get('/users');

            $response->assertForbidden();
        });

        it('prevents regular users from viewing user management', function () {
            $response = $this->actingAs($this->user)->get('/users');

            $response->assertForbidden();
        });

        it('shows users with search and role filtering', function () {
            // Create additional users for testing
            User::factory()->create(['name' => 'John Doe', 'role' => 'user']);
            User::factory()->create(['name' => 'Jane Smith', 'role' => 'checker']);

            $response = $this->actingAs($this->ceo)->get('/users?search=John&role=user');

            $response->assertSuccessful();
            $response->assertInertia(
                fn($page) =>
                $page->component('Users/Index')
                    ->has('users')
                    ->has('filters')
            );
        });
    });

    describe('User profile viewing', function () {
        it('allows users to view their own profile', function () {
            $response = $this->actingAs($this->user)->get("/users/{$this->user->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Show'));
        });

        it('allows CEOs to view any user profile', function () {
            $response = $this->actingAs($this->ceo)->get("/users/{$this->user->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Show'));
        });

        it('allows registrators to view any user profile', function () {
            $response = $this->actingAs($this->registrator)->get("/users/{$this->user->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Show'));
        });

        it('prevents users from viewing other user profiles', function () {
            $otherUser = User::factory()->create();
            $response = $this->actingAs($this->user)->get("/users/{$otherUser->id}");

            $response->assertForbidden();
        });
    });

    describe('Role assignment', function () {
        it('allows CEOs to update user roles', function () {
            $response = $this->actingAs($this->ceo)
                ->putJson("/users/{$this->user->id}/role", ['role' => 'checker']);

            $response->assertSuccessful();
            $response->assertJson(['message' => 'User role updated successfully']);

            $this->user->refresh();
            expect($this->user->role)->toBe('checker');
        });

        it('prevents non-CEOs from updating user roles', function () {
            $response = $this->actingAs($this->registrator)
                ->putJson("/users/{$this->user->id}/role", ['role' => 'checker']);

            $response->assertForbidden();
        });

        it('validates role values', function () {
            $response = $this->actingAs($this->ceo)
                ->putJson("/users/{$this->user->id}/role", ['role' => 'invalid_role']);

            $response->assertStatus(422);
        });

        it('prevents changing role of the last CEO', function () {
            $response = $this->actingAs($this->ceo)
                ->putJson("/users/{$this->ceo->id}/role", ['role' => 'user']);

            $response->assertStatus(422);
            $response->assertJson(['message' => 'Cannot change role of the last CEO']);
        });
    });

    describe('User profile updates', function () {
        it('allows users to update their own profile', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/users/{$this->user->id}", [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'phone_number' => '+1234567890',
                    'region' => 'Updated Region',
                ]);

            $response->assertSuccessful();
            $response->assertJson(['message' => 'User updated successfully']);

            $this->user->refresh();
            expect($this->user->name)->toBe('Updated Name');
            expect($this->user->email)->toBe('updated@example.com');
        });

        it('allows CEOs to update any user profile', function () {
            $response = $this->actingAs($this->ceo)
                ->putJson("/users/{$this->user->id}", [
                    'name' => 'CEO Updated Name',
                    'email' => 'ceo-updated@example.com',
                ]);

            $response->assertSuccessful();

            $this->user->refresh();
            expect($this->user->name)->toBe('CEO Updated Name');
        });

        it('validates email uniqueness', function () {
            $otherUser = User::factory()->create(['email' => 'existing@example.com']);

            $response = $this->actingAs($this->user)
                ->putJson("/users/{$this->user->id}", [
                    'name' => 'Updated Name',
                    'email' => 'existing@example.com',
                ]);

            $response->assertStatus(422);
        });

        it('allows same user to keep their email', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/users/{$this->user->id}", [
                    'name' => 'Updated Name',
                    'email' => $this->user->email, // Same email
                ]);

            $response->assertSuccessful();
        });
    });

    describe('User deletion', function () {
        it('allows CEOs to delete users', function () {
            $response = $this->actingAs($this->ceo)
                ->deleteJson("/users/{$this->user->id}");

            $response->assertSuccessful();
            $response->assertJson(['message' => 'User deleted successfully']);

            $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
        });

        it('prevents non-CEOs from deleting users', function () {
            $response = $this->actingAs($this->registrator)
                ->deleteJson("/users/{$this->user->id}");

            $response->assertForbidden();
        });

        it('prevents users from deleting themselves', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/users/{$this->user->id}");

            $response->assertForbidden();
        });

        it('prevents deletion of the last CEO', function () {
            // Create another CEO first
            $anotherCeo = User::factory()->ceo()->create();

            // Delete the other CEO
            $this->actingAs($this->ceo)->deleteJson("/users/{$anotherCeo->id}");

            // Now try to delete the last CEO
            $response = $this->actingAs($this->ceo)
                ->deleteJson("/users/{$this->ceo->id}");

            $response->assertStatus(422);
            $response->assertJson(['message' => 'Cannot delete the last CEO user']);
        });
    });

    describe('User statistics', function () {
        it('allows CEOs to view user statistics', function () {
            $response = $this->actingAs($this->ceo)->get('/users/statistics');

            $response->assertSuccessful();
            $response->assertJsonStructure([
                'total_users',
                'users_by_role',
                'recent_users',
            ]);
        });

        it('allows registrators to view user statistics', function () {
            $response = $this->actingAs($this->registrator)->get('/users/statistics');

            $response->assertSuccessful();
        });

        it('prevents other roles from viewing user statistics', function () {
            $response = $this->actingAs($this->checker)->get('/users/statistics');

            $response->assertForbidden();
        });

        it('returns correct user statistics', function () {
            // Create additional users
            User::factory()->create(['role' => 'user']);
            User::factory()->create(['role' => 'user']);

            $response = $this->actingAs($this->ceo)->get('/users/statistics');

            $response->assertSuccessful();
            $response->assertJson([
                'total_users' => 6, // ceo, registrator, checker, user, + 2 new users
                'users_by_role' => [
                    'user' => 3,
                    'checker' => 1,
                    'registrator' => 1,
                    'ceo' => 1,
                ],
            ]);
        });
    });
});
