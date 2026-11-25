<?php

use App\Filament\Fm\Resources\TicketResource\Pages\EditTicket;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Ticket;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Pages\MediaManager;

function makeMediaFile(string $name = 'plan.pdf'): File
{
    return File::create([
        'name' => $name,
        'size' => 1024,
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'uploaded_by_user_id' => auth()->id(),
    ]);
}

it('renders the media manager page in the FM panel', function () {
    loginAsAdminInFmPanel();

    Livewire::test(MediaManager::class)->assertSuccessful();
});

it('scopes media files and folders to the current tenant', function () {
    loginAsAdminInFmPanel();

    $eigeneDatei = makeMediaFile('eigene.pdf');
    Folder::create(['name' => 'Verträge']);

    expect(File::query()->count())->toBe(1);
    expect(Folder::query()->count())->toBe(1);

    $fremdeOrganisation = Organization::factory()->create();

    inOrganization($fremdeOrganisation, function (): void {
        expect(File::query()->count())->toBe(0);
        expect(Folder::query()->count())->toBe(0);

        makeMediaFile('fremde.pdf');

        expect(File::query()->count())->toBe(1);
    });

    expect(File::query()->count())->toBe(1);
    expect(File::query()->first()->name)->toBe('eigene.pdf');
});

it('attaches media files to a ticket through the media picker', function () {
    loginAsAdminInFmPanel();

    $ticket = Ticket::factory()->create();
    $datei = makeMediaFile('protokoll.pdf');

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->fillForm(['files' => [$datei->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ticket->fresh()->files()->count())->toBe(1);
    expect($ticket->fresh()->files()->first()->name)->toBe('protokoll.pdf');
});

it('attaches media files to an asset and a property', function () {
    loginAsAdminInFmPanel();

    $asset = Asset::factory()->create();
    $datei = makeMediaFile('datenblatt.pdf');

    $asset->files()->attach($datei->getKey(), ['collection' => 'files']);

    expect($asset->files()->count())->toBe(1);
});
