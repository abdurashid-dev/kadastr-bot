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
                fn($page) => $page->component('Users/Index')
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
                ->put("/users/{$this->user->id}/role", ['role' => 'checker']);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('success', 'User role updated successfully');

            $this->user->refresh();
            expect($this->user->role)->toBe('checker');
        });

        it('prevents non-CEOs from updating user roles', function () {
            $response = $this->actingAs($this->registrator)
                ->put("/users/{$this->user->id}/role", ['role' => 'checker']);

            $response->assertForbidden();
        });

        it('validates role values', function () {
            $response = $this->actingAs($this->ceo)
                ->put("/users/{$this->user->id}/role", ['role' => 'invalid_role']);

            $response->assertRedirect();
            $response->assertSessionHasErrors(['role']);
        });

        it('prevents changing role of the last CEO', function () {
            $response = $this->actingAs($this->ceo)
                ->put("/users/{$this->ceo->id}/role", ['role' => 'user']);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('error', 'Cannot change role of the last CEO');
        });
    });

    describe('User profile updates', function () {
        it('allows users to update their own profile', function () {
            $response = $this->actingAs($this->user)
                ->put("/users/{$this->user->id}", [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'phone_number' => '+1234567890',
                    'region' => 'Updated Region',
                ]);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('success', 'User updated successfully');

            $this->user->refresh();
            expect($this->user->name)->toBe('Updated Name');
            expect($this->user->email)->toBe('updated@example.com');
        });

        it('allows CEOs to update any user profile', function () {
            $response = $this->actingAs($this->ceo)
                ->put("/users/{$this->user->id}", [
                    'name' => 'CEO Updated Name',
                    'email' => 'ceo-updated@example.com',
                ]);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('success', 'User updated successfully');

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
                ->put("/users/{$this->user->id}", [
                    'name' => 'Updated Name',
                    'email' => $this->user->email, // Same email
                ]);

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('success', 'User updated successfully');
        });
    });

    describe('User deletion', function () {
        it('allows CEOs to delete users', function () {
            $response = $this->actingAs($this->ceo)
                ->delete("/users/{$this->user->id}");

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('success', 'User deleted successfully');

            $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
        });

        it('prevents non-CEOs from deleting users', function () {
            $response = $this->actingAs($this->registrator)
                ->delete("/users/{$this->user->id}");

            $response->assertForbidden();
        });

        it('prevents users from deleting themselves', function () {
            $response = $this->actingAs($this->user)
                ->delete("/users/{$this->user->id}");

            $response->assertForbidden();
        });

        it('prevents deletion of the last CEO', function () {
            // Create another CEO first
            $anotherCeo = User::factory()->ceo()->create();

            // Delete the other CEO
            $this->actingAs($this->ceo)->delete("/users/{$anotherCeo->id}");

            // Now try to delete the last CEO
            $response = $this->actingAs($this->ceo)
                ->delete("/users/{$this->ceo->id}");

            $response->assertRedirect(route('users.index'));
            $response->assertSessionHas('error', 'Cannot delete the last CEO user');
        });
    });

    describe('User statistics', function () {
        it('allows CEOs to view user statistics', function () {
            $response = $this->actingAs($this->ceo)->get('/users/statistics');

            $response->assertSuccessful();
            $response->assertInertia(
                fn($page) => $page
                    ->component('Users/Statistics')
                    ->has('stats')
                    ->has('stats.total_users')
                    ->has('stats.users_by_role')
                    ->has('stats.recent_users')
            );
        });

        it('allows registrators to view user statistics', function () {
            $response = $this->actingAs($this->registrator)->get('/users/statistics');

            $response->assertSuccessful();
            $response->assertInertia(fn($page) => $page->component('Users/Statistics'));
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
            $response->assertInertia(
                fn($page) => $page
                    ->component('Users/Statistics')
                    ->where('stats.total_users', 6) // ceo, registrator, checker, user, + 2 new users
                    ->where('stats.users_by_role.user', 3)
                    ->where('stats.users_by_role.checker', 1)
                    ->where('stats.users_by_role.registrator', 1)
                    ->where('stats.users_by_role.ceo', 1)
            );
        });
    });
});
