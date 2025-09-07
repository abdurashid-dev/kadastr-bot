<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('can switch to Uzbek Latin locale', function () {
    App::setLocale('uz-latn');

    expect(__('messages.welcome'))->toBe('Xush kelibsiz');
    expect(__('messages.dashboard'))->toBe('Boshqaruv paneli');
    expect(__('messages.users'))->toBe('Foydalanuvchilar');
});

it('can switch to Uzbek Cyrillic locale', function () {
    App::setLocale('uz-cyrl');

    expect(__('messages.welcome'))->toBe('Хуш келибсиз');
    expect(__('messages.dashboard'))->toBe('Бошқарув панели');
    expect(__('messages.users'))->toBe('Фойдаланувчилар');
});

it('falls back to English when locale is not available', function () {
    App::setLocale('invalid-locale');

    expect(__('messages.welcome'))->toBe('Welcome');
    expect(__('messages.dashboard'))->toBe('Dashboard');
});

it('can change language via language controller', function () {
    $response = $this->post('/language', ['locale' => 'uz-latn']);

    $response->assertRedirect();
    expect(Session::get('locale'))->toBe('uz-latn');
});

it('validates locale in language controller', function () {
    $response = $this->post('/language', ['locale' => 'invalid-locale']);

    $response->assertSessionHasErrors('locale');
});

it('includes locale data in inertia responses', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(
        fn($page) =>
        $page->has('locale')
            ->has('available_locales')
    );
});
