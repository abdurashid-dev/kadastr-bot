<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ceo = User::factory()->create(['role' => 'ceo']);
    $this->registrator = User::factory()->create(['role' => 'registrator']);
    $this->checker = User::factory()->create(['role' => 'checker']);
    $this->user = User::factory()->create(['role' => 'user']);
});

it('can access create user page as ceo', function () {
    $this->actingAs($this->ceo)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Users/Create')
                ->has('roles')
        );
});

it('cannot access create user page as non-ceo', function () {
    $this->actingAs($this->registrator)
        ->get(route('users.create'))
        ->assertForbidden();

    $this->actingAs($this->checker)
        ->get(route('users.create'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->get(route('users.create'))
        ->assertForbidden();
});

it('can create a new user with valid data', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone_number' => '+998901234567',
        'region' => 'Toshkent',
        'role' => 'user',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($this->ceo)
        ->post(route('users.store'), $userData)
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'User created successfully');

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone_number' => '+998901234567',
        'region' => 'Toshkent',
        'role' => 'user',
    ]);
});

it('can create a user with minimal required data', function () {
    $userData = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'checker',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($this->ceo)
        ->post(route('users.store'), $userData)
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role' => 'checker',
    ]);
});

it('validates required fields', function () {
    $this->actingAs($this->ceo)
        ->post(route('users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
});

it('validates email format and uniqueness', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($this->ceo)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['email']);

    $this->actingAs($this->ceo)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['email']);
});

it('validates phone number uniqueness', function () {
    User::factory()->create(['phone_number' => '+998901234567']);

    $this->actingAs($this->ceo)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+998901234567',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['phone_number']);
});

it('validates role selection', function () {
    $this->actingAs($this->ceo)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'invalid-role',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors(['role']);
});

it('validates password confirmation', function () {
    $this->actingAs($this->ceo)
        ->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors(['password']);
});

it('cannot create user as non-ceo', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'user',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($this->registrator)
        ->post(route('users.store'), $userData)
        ->assertForbidden();

    $this->actingAs($this->checker)
        ->post(route('users.store'), $userData)
        ->assertForbidden();

    $this->actingAs($this->user)
        ->post(route('users.store'), $userData)
        ->assertForbidden();
});

it('creates user with hashed password', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'user',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($this->ceo)
        ->post(route('users.store'), $userData);

    $user = User::where('email', 'john@example.com')->first();
    expect($user->password)->not->toBe('password123');
    expect(password_verify('password123', $user->password))->toBeTrue();
});

it('can create users with all role types', function () {
    $roles = ['user', 'checker', 'registrator', 'ceo'];

    foreach ($roles as $role) {
        $userData = [
            'name' => "Test {$role}",
            'email' => "{$role}@example.com",
            'role' => $role,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->actingAs($this->ceo)
            ->post(route('users.store'), $userData)
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'name' => "Test {$role}",
            'email' => "{$role}@example.com",
            'role' => $role,
        ]);
    }
});
