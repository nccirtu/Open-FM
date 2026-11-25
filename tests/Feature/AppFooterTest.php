<?php

use App\Support\OpenFm;
use Filament\Facades\Filament;

it('shows the attribution footer with version, year and license in every panel', function () {
    loginAsAdmin();

    $organization = defaultOrganization();

    foreach (['fm', 'pm', 'portal', 'contracts'] as $panel) {
        $this->get(Filament::getPanel($panel)->getUrl($organization))
            ->assertOk()
            ->assertSee(OpenFm::attribution())
            ->assertSee('v'.OpenFm::VERSION)
            ->assertSee((string) now()->year)
            ->assertSee(OpenFm::LICENSE);
    }

    $this->get(Filament::getPanel('admin')->getUrl())
        ->assertOk()
        ->assertSee(OpenFm::attribution());
});

it('shows the attribution footer on the login page', function () {
    $this->get(Filament::getPanel('fm')->getLoginUrl())
        ->assertOk()
        ->assertSee(OpenFm::attribution())
        ->assertSee(OpenFm::LICENSE);
});
