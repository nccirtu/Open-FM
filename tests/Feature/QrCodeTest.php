<?php

use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Portal\Resources\TicketResource as PortalTicketResource;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\PropertyResource\Pages\StructurePage;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Role;
use App\Models\StructureNode;
use App\Models\User;
use Cafm\QrCode\Filament\Pages\ScanQrCodePage;
use Cafm\QrCode\Filament\Resources\QrLabelFormatResource;
use Cafm\QrCode\Filament\Resources\QrLabelFormatResource\Pages\CreateQrLabelFormat;
use Cafm\QrCode\Filament\Resources\QrLabelFormatResource\Pages\ListQrLabelFormats;
use Cafm\QrCode\Models\QrLabelFormat;
use Cafm\QrCode\Pdf\LabelPdf;
use Cafm\QrCode\QrCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

function loginAsQrReporter(?Property $property = null): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();
    setPermissionsTeamId($organization->getKey());

    $reporter = User::factory()->create(['email' => 'qr-melder@example.com']);
    $reporter->organizations()->syncWithoutDetaching($organization);
    $reporter->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());

    if ($property !== null) {
        $reporter->properties()->attach($property);
    }

    test()->actingAs($reporter);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('portal');
    Filament::getPanel('portal')->boot();

    return $reporter;
}

beforeEach(fn () => requiresPlugin('qr-code'));

it('encodes a stable resolver address and renders SVG and PNG codes', function () {
    loginAsAdmin();

    $asset = Asset::factory()->create();

    $url = QrCode::url($asset);

    expect($url)->toEndWith('/qr/asset/'.$asset->getKey())
        ->and(QrCode::svg($asset))->toContain('<svg')
        ->and(QrCode::pngDataUri($asset))->toStartWith('data:image/png;base64,')
        ->and(QrCode::labelLines($asset)[0])->toBe($asset->name)
        ->and(QrCode::supports($asset))->toBeTrue();
});

it('redirects FM users to the record views and reporters to the portal report form', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    $node = StructureNode::query()->create(['property_id' => $property->getKey(), 'type' => 'room', 'name' => 'Raum 1.02']);
    $order = MaintenanceOrder::factory()->create();
    $organization = defaultOrganization();

    $this->get(QrCode::url($asset))
        ->assertRedirect(AssetResource::getUrl('view', ['record' => $asset], panel: 'fm', tenant: $organization));
    $this->get(QrCode::url($node))
        ->assertRedirect(PropertyResource::getUrl('structure', ['record' => $property], panel: 'fm', tenant: $organization));
    $this->get(QrCode::url($property))
        ->assertRedirect(PropertyResource::getUrl('view', ['record' => $property], panel: 'fm', tenant: $organization));
    $this->get(QrCode::url($order))
        ->assertRedirect(MaintenanceOrderResource::getUrl('view', ['record' => $order], panel: 'fm', tenant: $organization));

    loginAsQrReporter($property);

    $this->get(QrCode::url($asset))
        ->assertRedirect(PortalTicketResource::getUrl('create', ['property_id' => $property->getKey(), 'asset_id' => $asset->getKey()], panel: 'portal', tenant: $organization));
    $this->get(QrCode::url($node))
        ->assertRedirect(PortalTicketResource::getUrl('create', ['property_id' => $property->getKey(), 'structure_node_id' => $node->getKey()], panel: 'portal', tenant: $organization));
    $this->get(QrCode::url($order))->assertForbidden();
});

it('requires a login and hides records of other organizations', function () {
    loginAsAdmin();

    $asset = Asset::factory()->create();
    $url = QrCode::url($asset);

    $fremdeOrganisation = Organization::factory()->create();
    $fremdesAsset = inOrganization($fremdeOrganisation, fn (): Asset => Asset::factory()->create());

    $this->get(QrCode::url($fremdesAsset))->assertNotFound();
    $this->get(url('/qr/unbekannt/'.$asset->getKey()))->assertNotFound();
    $this->get(url('/qr/asset/00000000-0000-0000-0000-000000000000'))->assertNotFound();

    auth()->logout();

    $this->get($url)->assertRedirect(url('/login'));
});

it('seeds default label formats and renders labels as PDF', function () {
    loginAsAdmin();

    expect(QrLabelFormat::query()->count())->toBe(4)
        ->and(QrLabelFormat::default())->not->toBeNull()
        ->and(QrLabelFormat::default()->is_default)->toBeTrue();

    $property = Property::factory()->create();
    $nodes = collect([
        StructureNode::query()->create(['property_id' => $property->getKey(), 'type' => 'floor', 'name' => 'EG']),
        StructureNode::query()->create(['property_id' => $property->getKey(), 'type' => 'room', 'name' => 'Lager']),
    ]);

    $pdf = LabelPdf::for(QrLabelFormat::default())->build($nodes->map(fn (StructureNode $node) => QrCode::labelItem($node)))->output();

    expect($pdf)->toStartWith('%PDF');

    Livewire::test(StructurePage::class, ['record' => $property->getKey()])
        ->assertActionExists('printQrLabels')
        ->callAction('printQrLabels', ['format' => QrLabelFormat::default()->getKey()])
        ->assertFileDownloaded();

    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);

    Livewire::test(ViewAsset::class, ['record' => $asset->getKey()])
        ->assertActionExists('qrCode')
        ->callAction('qrCode', ['format' => QrLabelFormat::default()->getKey()])
        ->assertFileDownloaded();
});

it('labels maintenance orders with the translated due date', function () {
    loginAsAdmin();

    $order = MaintenanceOrder::factory()->create(['due_at' => '2026-11-05']);

    $lines = QrCode::labelLines($order);

    expect($lines)->toContain(__('maintenance.fields.due_at').' 05.11.2026')
        ->and(implode(' ', $lines))->not->toContain('maintenance.');
});

it('lets admins manage label formats and keeps a single default', function () {
    loginAsAdmin();
    Filament::setCurrentPanel('admin');

    setPermissionsTeamId(null);

    expect(QrLabelFormatResource::canViewAny())->toBeTrue()
        ->and(QrLabelFormatResource::canCreate())->toBeTrue();

    Livewire::test(ListQrLabelFormats::class)
        ->assertOk()
        ->assertCanSeeTableRecords(QrLabelFormat::query()->get())
        ->assertTableActionExists('preview');

    Livewire::test(CreateQrLabelFormat::class)
        ->fillForm([
            'name' => 'Rolle 40 × 20 mm',
            'layout' => QrLabelFormat::LAYOUT_SIDE,
            'text_lines' => 2,
            'font_size_pt' => 6,
            'qr_size_mm' => 16,
            'page_width_mm' => 40,
            'page_height_mm' => 20,
            'label_width_mm' => 40,
            'label_height_mm' => 20,
            'columns' => 1,
            'rows' => 1,
            'is_default' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(QrLabelFormat::query()->where('is_default', true)->count())->toBe(1)
        ->and(QrLabelFormat::default()->name)->toBe('Rolle 40 × 20 mm');
});

it('renders the scanner page in the FM and portal panels', function () {
    loginAsAdmin();

    $this->get(ScanQrCodePage::getUrl(panel: 'fm', tenant: defaultOrganization()))
        ->assertOk()
        ->assertSee('qrScanner')
        ->assertSee(url('/qr/'));

    loginAsQrReporter();

    $this->get(ScanQrCodePage::getUrl(panel: 'portal', tenant: defaultOrganization()))
        ->assertOk()
        ->assertSee('qrScanner');
});
