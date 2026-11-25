<?php

use App\Filament\PanelSwitcherMenuItems;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Filament\Navigation\MenuItem;

/**
 * @return array<string, MenuItem>
 */
function panelSwitcherItemsByLabel(): array
{
    return collect(PanelSwitcherMenuItems::make())
        ->mapWithKeys(fn (MenuItem $item): array => [$item->getLabel() => $item])
        ->all();
}

it('shows an Admin all other panels with the current tenant in the URLs', function () {
    loginAsAdmin();

    $items = panelSwitcherItemsByLabel();

    expect($items['Administration']->isVisible())->toBeTrue();
    expect($items['Gebäudemanagement']->isVisible())->toBeTrue();
    expect($items['Service-Portal']->isVisible())->toBeTrue();
    expect($items['Projektmanagement']->isVisible())->toBeFalse();

    expect($items['Administration']->getUrl())->toContain('/admin');
    expect($items['Gebäudemanagement']->getUrl())->toContain('/fm/demo');
    expect($items['Service-Portal']->getUrl())->toContain('/portal/demo');
});

it('resolves the default tenant when switching out of the global admin panel', function () {
    loginAsAdmin();

    Filament::setTenant(null);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $items = panelSwitcherItemsByLabel();

    expect($items['Administration']->isVisible())->toBeFalse();
    expect($items['Gebäudemanagement']->isVisible())->toBeTrue();
    expect($items['Gebäudemanagement']->getUrl())->toContain('/fm/demo');
    expect($items['Projektmanagement']->getUrl())->toContain('/pm/demo');
});

it('hides panels the user has no role for', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();
    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => 'nur.user@example.com']);
    $user->organizations()->attach($organization);
    $user->assignRole('User');

    test()->actingAs($user);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('pm');
    Filament::bootCurrentPanel();

    $items = panelSwitcherItemsByLabel();

    expect($items['Administration']->isVisible())->toBeFalse();
    expect($items['Gebäudemanagement']->isVisible())->toBeFalse();
    expect($items['Service-Portal']->isVisible())->toBeFalse();
});

it('renders the switcher entries in the topbar user menu', function () {
    loginAsAdmin();

    Livewire\Livewire::test(Topbar::class)
        ->assertSee('Administration')
        ->assertSee('Gebäudemanagement')
        ->assertSee('Service-Portal');
});
