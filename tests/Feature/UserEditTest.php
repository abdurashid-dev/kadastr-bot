<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ceo = User::factory()->create(['role' => 'ceo']);
    $this->registrator = User::factory()->create(['role' => 'registrator']);
    $this->checker = User::factory()->create(['role' => 'checker']);
    $this->user = User::factory()->create(['role' => 'user']);
    $this->testUser = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone_number' => '998901234567',
        'region' => 'Toshkent',
        'role' => 'user',
    ]);
});

it('can access edit user page as ceo', function () {
    $this->actingAs($this->ceo)
        ->get(route('users.edit', $this->testUser))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Users/Edit')
                ->has('user')
                ->has('roles')
                ->has('regions')
        );
});

it('can access edit user page as registrator for non-admin users', function () {
    $this->actingAs($this->registrator)
        ->get(route('users.edit', $this->testUser))
        ->assertOk();
});

it('cannot access edit user page as registrator for other registrators', function () {
    $otherRegistrator = User::factory()->create(['role' => 'registrator']);

    $this->actingAs($this->registrator)
        ->get(route('users.edit', $otherRegistrator))
        ->assertForbidden();
});

it('cannot access edit user page as checker', function () {
    $this->actingAs($this->checker)
        ->get(route('users.edit', $this->testUser))
        ->assertForbidden();
});

it('can access edit own profile as regular user', function () {
    $this->actingAs($this->user)
        ->get(route('users.edit', $this->user))
        ->assertOk();
});

it('cannot access edit other users profile as regular user', function () {
    $this->actingAs($this->user)
        ->get(route('users.edit', $this->testUser))
        ->assertForbidden();
});

it('can update user with valid data', function () {
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone_number' => '998901234568',
        'region' => 'Samarqand',
        'role' => 'checker',
    ];

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'User updated successfully');

    $this->assertDatabaseHas('users', [
        'id' => $this->testUser->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone_number' => '998901234568',
        'region' => 'Samarqand',
        'role' => 'checker',
    ]);
});

it('can update user with minimal required data', function () {
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
    ];

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $this->testUser->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
    ]);
});

it('can update user with new password', function () {
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ];

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertRedirect(route('users.index'));

    $this->testUser->refresh();
    expect(password_verify('newpassword123', $this->testUser->password))->toBeTrue();
});

it('validates required fields', function () {
    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [])
        ->assertSessionHasErrors(['name', 'email', 'role']);
});

it('validates email format and uniqueness', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'role' => 'user',
        ])
        ->assertSessionHasErrors(['email']);

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'role' => 'user',
        ])
        ->assertSessionHasErrors(['email']);
});

it('validates phone number uniqueness', function () {
    User::factory()->create(['phone_number' => '998901234568']);

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '998901234568',
            'role' => 'user',
        ])
        ->assertSessionHasErrors(['phone_number']);
});

it('validates role selection', function () {
    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'invalid-role',
        ])
        ->assertSessionHasErrors(['role']);
});

it('validates password confirmation when password is provided', function () {
    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user',
            'password' => 'newpassword123',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors(['password']);
});

it('cannot update user as non-authorized user', function () {
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
    ];

    $this->actingAs($this->checker)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertForbidden();

    $this->actingAs($this->user)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertForbidden();
});

it('can update own profile as regular user', function () {
    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
    ];

    $this->actingAs($this->user)
        ->put(route('users.update', $this->user), $updateData)
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('can update users with all role types', function () {
    $roles = ['user', 'checker', 'registrator', 'ceo'];

    foreach ($roles as $role) {
        $updateData = [
            'name' => "Updated {$role}",
            'email' => "updated{$role}@example.com",
            'role' => $role,
        ];

        $this->actingAs($this->ceo)
            ->put(route('users.update', $this->testUser), $updateData)
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $this->testUser->id,
            'name' => "Updated {$role}",
            'email' => "updated{$role}@example.com",
            'role' => $role,
        ]);
    }
});

it('does not update password when not provided', function () {
    $originalPassword = $this->testUser->password;

    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'user',
    ];

    $this->actingAs($this->ceo)
        ->put(route('users.update', $this->testUser), $updateData)
        ->assertRedirect(route('users.index'));

    $this->testUser->refresh();
    expect($this->testUser->password)->toBe($originalPassword);
});
