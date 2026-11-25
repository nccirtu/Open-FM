<?php

namespace App\Support;

use App\Filament\Energy\Pages\RecordReadingPage;
use App\Filament\Energy\Resources\MeterResource;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Portal\Resources\TicketResource as PortalTicketResource;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Filament\Waste\Resources\WasteContainers\WasteContainerResource;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\User;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Support\Plugins\Plugins;
use Cafm\QrCode\QrCode;
use Cafm\QrCode\Support\QrTarget;
use Illuminate\Database\Eloquent\Model;

class QrCodeTargets
{
    public static function register(): void
    {
        QrCode::registry()
            ->organizationUsing(function (Model $model): ?Organization {
                $organizationId = $model->getAttribute('organization_id');

                return filled($organizationId) ? Organization::query()->find($organizationId) : null;
            })
            ->authorizeUsing(function (User $user, Model $model, ?Organization $organization): bool {
                if ($organization === null
                    || ! Plugins::availableFor('qr-code', $organization)
                    || ! $user->organizations()->whereKey($organization->getKey())->exists()) {
                    return false;
                }

                setPermissionsTeamId($organization->getKey());

                return true;
            });

        QrCode::register(
            QrTarget::make(
                type: 'asset',
                modelClass: Asset::class,
                url: fn (Asset $asset, User $user, ?Organization $organization): ?string => match (true) {
                    self::usesFm($user) => AssetResource::getUrl('view', ['record' => $asset], panel: 'fm', tenant: $organization),
                    self::isReporter($user) => self::portalReportUrl($organization, ['property_id' => $asset->property_id, 'asset_id' => $asset->getKey()]),
                    default => null,
                },
                labelLines: fn (Asset $asset): array => [
                    $asset->name,
                    filled($asset->asset_number) ? __('assets.fields.asset_number').' '.$asset->asset_number : '',
                    implode(' · ', array_filter([$asset->property?->property_number, $asset->structureNode?->name])),
                ],
            ),
            QrTarget::make(
                type: 'node',
                modelClass: StructureNode::class,
                url: fn (StructureNode $node, User $user, ?Organization $organization): ?string => match (true) {
                    self::isWasteCollectionPoint($node) && $user->can('confirm', WastePickup::class) => ConfirmPickupsPage::getUrl(['property' => $node->property_id, 'node' => $node->getKey()], panel: 'waste', tenant: $organization),
                    self::usesFm($user) => PropertyResource::getUrl('structure', ['record' => $node->property], panel: 'fm', tenant: $organization),
                    self::isReporter($user) => self::portalReportUrl($organization, ['property_id' => $node->property_id, 'structure_node_id' => $node->getKey()]),
                    default => null,
                },
                labelLines: fn (StructureNode $node): array => [
                    $node->name,
                    $node->type?->getLabel() ?? '',
                    $node->property?->property_number ?? '',
                ],
            ),
            QrTarget::make(
                type: 'property',
                modelClass: Property::class,
                url: fn (Property $property, User $user, ?Organization $organization): ?string => match (true) {
                    self::usesFm($user) => PropertyResource::getUrl('view', ['record' => $property], panel: 'fm', tenant: $organization),
                    self::isReporter($user) => self::portalReportUrl($organization, ['property_id' => $property->getKey()]),
                    default => null,
                },
                labelLines: fn (Property $property): array => [
                    $property->property_number,
                    $property->street ?? '',
                    trim(($property->zip_code ?? '').' '.($property->city ?? '')),
                ],
                title: fn (Property $property): string => __('property.model').' '.$property->property_number,
            ),
            QrTarget::make(
                type: 'maintenance-order',
                modelClass: MaintenanceOrder::class,
                url: fn (MaintenanceOrder $order, User $user, ?Organization $organization): ?string => self::usesFm($user)
                    ? MaintenanceOrderResource::getUrl('view', ['record' => $order], panel: 'fm', tenant: $organization)
                    : null,
                labelLines: fn (MaintenanceOrder $order): array => [
                    $order->plan?->name ?? __('maintenance.orders.model'),
                    $order->asset?->name ?? '',
                    $order->due_at ? __('maintenance.fields.due_at').' '.$order->due_at->format('d.m.Y') : '',
                ],
            ),
            QrTarget::make(
                type: 'meter',
                modelClass: Meter::class,
                url: fn (Meter $meter, User $user, ?Organization $organization): ?string => match (true) {
                    ! $meter->isVirtual() && $user->can('create', MeterReading::class) => RecordReadingPage::getUrl(['meter' => $meter->getKey()], panel: 'energy', tenant: $organization),
                    $user->can('view', $meter) => MeterResource::getUrl('view', ['record' => $meter], panel: 'energy', tenant: $organization),
                    default => null,
                },
                labelLines: fn (Meter $meter): array => [
                    $meter->name,
                    filled($meter->number) ? __('energy.fields.number').' '.$meter->number : '',
                    implode(' · ', array_filter([$meter->property?->property_number, $meter->medium?->getLabel()])),
                ],
            ),
            QrTarget::make(
                type: 'waste-container',
                modelClass: WasteContainer::class,
                url: fn (WasteContainer $container, User $user, ?Organization $organization): ?string => match (true) {
                    $user->can('confirm', WastePickup::class) => ConfirmPickupsPage::getUrl(['container' => $container->getKey()], panel: 'waste', tenant: $organization),
                    $user->can('view', $container) => WasteContainerResource::getUrl('edit', ['record' => $container], panel: 'waste', tenant: $organization),
                    default => null,
                },
                labelLines: fn (WasteContainer $container): array => [
                    $container->number,
                    trim(($container->fraction?->name ?? '').' · '.number_format($container->volume_l, 0, ',', '.').' l'),
                    implode(' › ', array_filter([$container->property?->property_number, $container->structureNode?->name])),
                ],
            ),
        );
    }

    private static function usesFm(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'FM', 'Dienstleister']);
    }

    private static function isReporter(User $user): bool
    {
        return $user->hasRole('Melder');
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function portalReportUrl(?Organization $organization, array $parameters): string
    {
        return PortalTicketResource::getUrl('create', array_filter($parameters), panel: 'portal', tenant: $organization);
    }

    private static function isWasteCollectionPoint(StructureNode $node): bool
    {
        return WasteContainer::query()
            ->withoutGlobalScopes()
            ->where('structure_node_id', $node->getKey())
            ->where('is_active', true)
            ->exists();
    }
}
