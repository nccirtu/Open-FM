<?php

use App\Filament\Admin\Resources\OrganizationResource;
use App\Filament\Contracts\Resources\ContractResource;
use App\Filament\Fm\Resources\CompanyResource;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Fm\Resources\MaintenancePlanResource;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource;
use App\Filament\Fm\Resources\TicketResource;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectTemplateResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\TaskResource;
use Filament\Facades\Filament;

it('uses the Filament default content width in every panel', function () {
    foreach (['admin', 'fm', 'pm', 'contracts', 'portal'] as $panel) {
        expect(Filament::getPanel($panel)->getMaxContentWidth())->toBeNull();
    }
});

it('groups the FM navigation by properties, tickets, maintenance and partners', function () {
    loginAsAdminInFmPanel();

    expect(PropertyResource::getNavigationGroup())->toBe(__('app.nav_groups.objects'))
        ->and(AssetResource::getNavigationGroup())->toBe(__('app.nav_groups.objects'))
        ->and(TicketResource::getNavigationGroup())->toBe(__('app.nav_groups.tickets'))
        ->and(CompanyResource::getNavigationGroup())->toBe(__('app.nav_groups.partners'))
        ->and([MaintenancePlanResource::getNavigationSort(), MaintenanceOrderResource::getNavigationSort(), MaintenancePlanTemplateResource::getNavigationSort()])->toBe([10, 20, 30])
        ->and(collect(Filament::getPanel('fm')->getNavigationGroups())->map(fn ($group) => $group->getLabel())->values()->all())
        ->toBe([__('app.nav_groups.objects'), __('app.nav_groups.tickets'), __('maintenance.navigation_group'), __('app.nav_groups.partners'), __('form-builder.navigation.group')]);
});

it('groups the PM navigation by projects and properties', function () {
    loginAsAdmin();

    expect([ProjectResource::getNavigationSort(), TaskResource::getNavigationSort(), ProjectTemplateResource::getNavigationSort()])->toBe([10, 20, 30])
        ->and(ProjectResource::getNavigationGroup())->toBe(__('projects.navigation_group'))
        ->and(collect(Filament::getPanel('pm')->getNavigationGroups())->map(fn ($group) => $group->getLabel())->values()->all())
        ->toBe([__('projects.navigation_group'), __('app.nav_groups.objects'), __('form-builder.navigation.group')])
        ->and(ContractResource::getNavigationSort())->toBe(10)
        ->and(OrganizationResource::getNavigationGroup())->toBe(__('app.admin_groups.management'));
});
