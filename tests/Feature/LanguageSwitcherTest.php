<?php

use App\Http\Middleware\SetLocale;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('saves the personal locale preference and redirects back', function () {
    $user = loginAsAdmin();

    $this->from('/pm/demo')
        ->get(route('locale.switch', ['locale' => 'en']))
        ->assertRedirect('/pm/demo');

    expect($user->fresh()->locale)->toBe('en');
});

it('rejects unknown locales', function () {
    loginAsAdmin();

    $this->get(route('locale.switch', ['locale' => 'xx']))->assertNotFound();
});

it('requires authentication to switch the language', function () {
    $this->get(route('locale.switch', ['locale' => 'en']))->assertRedirect(route('login'));
});

it('prefers the user locale over the tenant default locale', function () {
    $organization = Organization::factory()->create(['default_locale' => 'en']);

    $user = User::factory()->create(['locale' => 'de']);

    inOrganization($organization, function () use ($user): void {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        (new SetLocale)->handle($request, fn ($r) => new Response);

        expect(app()->getLocale())->toBe('de');
    });
});

it('renders the language dropdown with all languages in the topbar', function () {
    loginAsAdmin();

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('Deutsch')
        ->assertSee('English')
        ->assertSee('/sprache/de')
        ->assertSee('/sprache/en');
});

it('renders the panel switcher translated for users with an English locale', function () {
    $user = loginAsAdmin();
    $user->update(['locale' => 'en']);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('Facility Management')
        ->assertSee('Choose language');
});
