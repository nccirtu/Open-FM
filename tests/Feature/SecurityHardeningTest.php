<?php

use App\Filament\Fm\Widgets\OpenTicketsWidget;
use App\Filament\Fm\Widgets\TicketStatsWidget;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('sends browser security headers and HSTS only over https', function () {
    $this->get('/up')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/up')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('ignores forwarded headers from proxies that are not trusted', function () {
    Route::get('/_test/client-ip', fn (Request $request): string => (string) $request->ip());

    $this->withHeaders(['X-Forwarded-For' => '203.0.113.9'])
        ->get('/_test/client-ip')
        ->assertOk()
        ->assertSee('127.0.0.1');
});

it('keeps API token management to admins', function () {
    $admin = loginAsAdmin();

    $fmUser = User::factory()->create();
    $fmUser->organizations()->attach(defaultOrganization()->getKey());
    $fmUser->assignRole('FM');

    expect($admin->can('create', ApiToken::class))->toBeTrue()
        ->and($fmUser->can('create', ApiToken::class))->toBeFalse()
        ->and($fmUser->can('viewAny', ApiToken::class))->toBeFalse();
});

it('hides ticket dashboard widgets from external service providers', function () {
    loginAsAdmin();

    $company = Company::factory()->create();
    $provider = User::factory()->create(['company_id' => $company->getKey()]);
    $provider->organizations()->attach(defaultOrganization()->getKey());
    $provider->assignRole('Dienstleister');

    $this->actingAs($provider);

    expect(OpenTicketsWidget::canView())->toBeFalse()
        ->and(TicketStatsWidget::canView())->toBeFalse();
});

it('hides ticket dashboard widgets from service providers without a company', function () {
    loginAsAdmin();

    $provider = User::factory()->create(['company_id' => null]);
    $provider->organizations()->attach(defaultOrganization()->getKey());
    $provider->assignRole('Dienstleister');

    $this->actingAs($provider);

    expect(TicketStatsWidget::canView())->toBeFalse();
});
