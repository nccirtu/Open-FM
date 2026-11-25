<?php

use App\Filament\Contracts\Resources\ContractResource\Pages\EditContract;
use App\Filament\Fm\Resources\MaintenanceOrderResource\Pages\EditMaintenanceOrder;
use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CostEntry;
use App\Models\MaintenanceOrder;
use App\Models\Ticket;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('captures receipts as cost entries on tickets, maintenance orders and contracts', function (string $model, string $pageClass, string $login) {
    requiresPlugin('receipt-capture');

    $login();
    Storage::fake('uploads');

    $owner = $model::factory()->create();
    $supplier = Company::query()->create(['name' => 'Rohrfrei GmbH']);

    $component = Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => $pageClass])
        ->assertOk()
        ->callAction(TestAction::make('captureReceipt')->table(), [
            'receipt_file' => UploadedFile::fake()->create('rechnung.pdf', 40, 'application/pdf'),
            'supplier_id' => $supplier->getKey(),
            'receipt_type' => 'expense',
            'invoice_number' => 'RF-2026-01',
            'booked_on' => '2026-09-04',
            'tax_rate' => '19',
            'gross_amount' => 238,
        ])
        ->assertHasNoActionErrors();

    $entry = $owner->costEntries()->sole();

    expect($entry)
        ->costable_type->toBe($owner->getMorphClass())
        ->costable_id->toBe($owner->getKey())
        ->project_id->toBeNull()
        ->organization_id->toBe(Filament::getTenant()->getKey())
        ->company_id->toBe($supplier->getKey())
        ->invoice_number->toBe('RF-2026-01')
        ->and((float) $entry->amount)->toBe(238.0)
        ->and((float) $entry->net_amount)->toBe(200.0)
        ->and($entry->receipt)->not->toBeNull();

    Storage::disk('uploads')->assertExists($entry->receipt);

    $withoutReceipt = $owner->costEntries()->create(['kind' => 'actual', 'amount' => 5, 'booked_on' => now()]);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => $pageClass])
        ->assertCanSeeTableRecords([$entry, $withoutReceipt])
        ->assertActionVisible(TestAction::make('previewReceipt')->table($entry))
        ->assertActionHidden(TestAction::make('previewReceipt')->table($withoutReceipt))
        ->callAction(TestAction::make('previewReceipt')->table($entry))
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('delete')->table($withoutReceipt));

    expect($owner->costEntries()->count())->toBe(1)
        ->and(CostEntriesRelationManager::getBadge($owner->fresh(), $pageClass))->toBe('1');
})->with([
    'ticket' => [Ticket::class, ViewTicket::class, 'loginAsAdminInFmPanel'],
    'asset' => [Asset::class, ViewAsset::class, 'loginAsAdmin'],
    'maintenance order' => [MaintenanceOrder::class, EditMaintenanceOrder::class, 'loginAsAdminInFmPanel'],
    'contract' => [Contract::class, EditContract::class, 'loginAsAdminInContractsPanel'],
]);

it('creates a plain cost entry via the form and keeps project entries untouched', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewTicket::class])
        ->callAction(TestAction::make('create')->table(), [
            'kind' => 'actual',
            'amount' => 99.9,
            'booked_on' => '2026-09-04',
            'description' => 'Anfahrt',
        ])
        ->assertHasNoActionErrors();

    expect(CostEntry::query()->where('costable_id', $ticket->getKey())->count())->toBe(1)
        ->and($ticket->costEntries()->sole()->description)->toBe('Anfahrt');
});

it('shows the cost entries relation manager on the view ticket page', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();

    Livewire::test(ViewTicket::class, ['record' => $ticket->getKey()])
        ->assertOk()
        ->assertSee(__('projects.costs.section'));

    Livewire::test(CostEntriesRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => ViewTicket::class,
    ])->assertSuccessful();
});
