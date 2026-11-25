<?php

use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\EntityMediaFolder;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Services\EntityFolders;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;

it('creates the mirrored folder tree when entities are created', function () {
    loginAsAdmin();

    $property = Property::factory()->create(['property_number' => 'Objekt 7']);
    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lager']);
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Pumpe']);
    $plan = MaintenancePlan::factory()->create(['asset_id' => $asset->getKey(), 'name' => 'Jahreswartung']);
    $order = MaintenanceOrder::factory()->create(['maintenance_plan_id' => $plan->getKey(), 'due_at' => '2026-10-01']);
    $project = Project::factory()->create(['property_id' => $property->getKey(), 'name' => 'Umbau']);
    $ticket = Ticket::factory()->create(['property_id' => $property->getKey(), 'title' => 'Wasserschaden']);
    $contract = Contract::factory()->create(['name' => 'Wartungsvertrag']);
    $company = Company::query()->create(['name' => 'Rohrfrei GmbH']);

    expect(EntityFolders::pathFor($property))->toBe('Objekt 7')
        ->and(EntityFolders::pathFor($room))->toBe('Objekt 7/Struktur/Lager')
        ->and(EntityFolders::pathFor($asset))->toBe('Objekt 7/Assets/Pumpe')
        ->and(EntityFolders::pathFor($plan))->toBe('Objekt 7/Assets/Pumpe/Wartung/Jahreswartung')
        ->and(EntityFolders::pathFor($order))->toBe('Objekt 7/Assets/Pumpe/Wartung/Jahreswartung/2026-10-01')
        ->and(EntityFolders::pathFor($project))->toBe('Objekt 7/Projekte/Umbau')
        ->and(EntityFolders::pathFor($ticket))->toBe('Objekt 7/Störungen/#'.$ticket->ticket_number.' Wasserschaden')
        ->and(EntityFolders::pathFor($contract))->toBe('Verträge/Wartungsvertrag')
        ->and(EntityFolders::pathFor($company))->toBe('Firmen/Rohrfrei GmbH')
        ->and(Folder::query()->where('name', 'Assets')->count())->toBe(1)
        ->and(EntityMediaFolder::query()->count())->toBe(9);
});

it('mirrors renames and moves of an asset to its folder', function () {
    loginAsAdmin();

    $property = Property::factory()->create(['property_number' => 'A']);
    $other = Property::factory()->create(['property_number' => 'B']);
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Pumpe']);
    $folderId = EntityFolders::folderFor($asset)->getKey();

    $asset->update(['name' => 'Pumpe 2']);

    expect(EntityFolders::pathFor($asset->fresh()))->toBe('A/Assets/Pumpe 2');

    $asset->update(['property_id' => $other->getKey()]);

    expect(EntityFolders::pathFor($asset->fresh()))->toBe('B/Assets/Pumpe 2')
        ->and(EntityFolders::folderFor($asset->fresh())->getKey())->toBe($folderId);
});

it('skips folder creation without a tenant and catches up on first access', function () {
    loginAsAdmin();
    $organization = Filament::getTenant();

    Filament::setTenant(null, isQuiet: true);
    $property = Property::factory()->create(['organization_id' => $organization->getKey(), 'property_number' => 'Spät']);

    expect(EntityMediaFolder::query()->count())->toBe(0);

    Filament::setTenant($organization, isQuiet: true);

    expect(EntityFolders::pathFor($property))->toBe('Spät')
        ->and(EntityMediaFolder::query()->count())->toBe(1);
});

it('moves freshly uploaded root files into the entity folder when attached', function () {
    loginAsAdmin();

    $asset = Asset::factory()->create(['name' => 'Kessel']);
    $file = File::query()->create(['name' => 'datenblatt.pdf', 'size' => 10, 'extension' => 'pdf', 'mime_type' => 'application/pdf']);
    $elsewhere = File::query()->create(['name' => 'sonstiges.pdf', 'size' => 10, 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'folder_id' => Folder::query()->create(['name' => 'Archiv'])->getKey()]);

    Livewire::test(EditAsset::class, ['record' => $asset->getRouteKey()])
        ->fillForm(['files' => [$file->getKey(), $elsewhere->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($asset->files()->count())->toBe(2)
        ->and($file->fresh()->folder_id)->toBe(EntityFolders::folderFor($asset)->getKey())
        ->and($elsewhere->fresh()->folder->name)->toBe('Archiv');
});

it('stores captured receipts in the Belege folder and links them to the cost entry', function () {
    requiresPlugin('receipt-capture');

    loginAsAdmin();
    Storage::fake('uploads');

    $property = Property::factory()->create(['property_number' => 'Objekt 1']);
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lüftung']);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('captureReceipt')->table(), [
            'receipt_file' => UploadedFile::fake()->create('rechnung.pdf', 30, 'application/pdf'),
            'receipt_type' => 'expense',
            'booked_on' => '2026-09-04',
            'gross_amount' => 119,
        ])
        ->assertHasNoActionErrors();

    $entry = $asset->costEntries()->sole();
    $file = $entry->mediaFiles('receipt')->sole();

    expect(EntityFolders::pathOf($file->folder))->toBe('Objekt 1/Assets/Lüftung/Belege')
        ->and($file->name)->toBe('rechnung.pdf')
        ->and($entry->receipt)->not->toBeNull();

    Storage::disk('uploads')->assertExists($entry->receipt);
});

it('imports receipts uploaded through the plain form into the media manager', function () {
    loginAsAdmin();
    Storage::fake('uploads');

    $asset = Asset::factory()->create(['name' => 'Tor']);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('create')->table(), [
            'kind' => 'actual',
            'amount' => 50,
            'booked_on' => '2026-09-04',
            'receipt' => UploadedFile::fake()->create('bon.pdf', 5, 'application/pdf'),
        ])
        ->assertHasNoActionErrors();

    $entry = $asset->costEntries()->sole();
    $file = $entry->mediaFiles('receipt')->sole();

    expect($file->folder->name)->toBe(EntityFolders::RECEIPTS)
        ->and($entry->receipt)->toBe($file->getFirstMedia('default')->getPathRelativeToRoot());

    Storage::disk('uploads')->assertExists($entry->receipt);
});

it('lists only the entity folder in the documents tab and uploads into it', function () {
    loginAsAdmin();
    Storage::fake('uploads');

    $asset = Asset::factory()->create(['name' => 'Heizung']);
    $folder = EntityFolders::folderFor($asset);
    $inside = File::query()->create(['name' => 'plan.pdf', 'size' => 10, 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'folder_id' => $folder->getKey()]);
    $nested = File::query()->create(['name' => 'beleg.pdf', 'size' => 10, 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'folder_id' => EntityFolders::receiptsFolderFor($asset)->getKey()]);
    $outside = File::query()->create(['name' => 'fremd.pdf', 'size' => 10, 'extension' => 'pdf', 'mime_type' => 'application/pdf']);

    Livewire::test(FolderFilesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->assertOk()
        ->assertCanSeeTableRecords([$inside, $nested])
        ->assertCanNotSeeTableRecords([$outside])
        ->assertSee(EntityFolders::RECEIPTS)
        ->callAction(TestAction::make('upload')->table(), ['files' => [UploadedFile::fake()->create('neu.pdf', 8, 'application/pdf')]])
        ->assertHasNoActionErrors();

    expect(File::query()->where('folder_id', $folder->getKey())->pluck('name')->all())->toContain('neu.pdf')
        ->and(FolderFilesRelationManager::getBadge($asset, ViewAsset::class))->toBe('3');
});

it('backfills folders for existing records via folders:sync', function () {
    loginAsAdmin();
    $organization = Filament::getTenant();

    Filament::setTenant(null, isQuiet: true);
    $property = Property::factory()->create(['organization_id' => $organization->getKey(), 'property_number' => 'Alt']);
    $asset = Asset::factory()->create(['organization_id' => $organization->getKey(), 'property_id' => $property->getKey(), 'name' => 'Altanlage']);

    expect(EntityMediaFolder::query()->count())->toBe(0);

    $this->artisan('folders:sync', ['--organization' => $organization->getKey()])->assertSuccessful();

    Filament::setTenant($organization, isQuiet: true);

    expect(EntityFolders::pathFor($asset))->toBe('Alt/Assets/Altanlage')
        ->and(EntityMediaFolder::query()->count())->toBe(2);
});
