<?php

use App\Models\Asset;
use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Slimani\MediaManager\Models\File as MediaFile;

function seedPublicUploads(): array
{
    Storage::fake('public');
    Storage::disk('public')->put('ticket-photos/a.jpg', 'foto-a');
    Storage::disk('public')->put('waste-receipts/w.pdf', 'beleg-w');
    Storage::disk('public')->put('Objekt 1/Assets/Pumpe/b.jpg', 'foto-b');
    Storage::disk('public')->put('branding/logo.png', 'logo');

    $ticket = Ticket::factory()->create(['photos' => ['abc' => 'ticket-photos/a.jpg', 'def' => 'ticket-photos/fehlt.jpg']]);
    $asset = Asset::factory()->create(['image' => 'Objekt 1/Assets/Pumpe/b.jpg']);

    $file = MediaFile::query()->create(['name' => 'plan.pdf', 'size' => 4, 'extension' => 'pdf', 'mime_type' => 'application/pdf']);
    $media = $file->addMediaFromString('plan')->usingFileName('plan.pdf')->toMediaCollection('default', 'public');

    return compact('ticket', 'asset', 'media');
}

it('moves user uploads from the public to the uploads disk and keeps the stored paths', function () {
    loginAsAdmin();
    ['ticket' => $ticket, 'asset' => $asset, 'media' => $media] = seedPublicUploads();

    $this->artisan('uploads:make-private')
        ->expectsOutputToContain('Dateien verschoben: 4')
        ->expectsOutputToContain('Nicht gefunden (Verweis ohne Datei): 1')
        ->expectsOutputToContain('Medien-Einträge umgestellt: 1')
        ->assertSuccessful();

    foreach (['ticket-photos/a.jpg', 'waste-receipts/w.pdf', 'Objekt 1/Assets/Pumpe/b.jpg', $media->getPathRelativeToRoot()] as $path) {
        Storage::disk('uploads')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    Storage::disk('public')->assertExists('branding/logo.png');
    Storage::disk('uploads')->assertMissing('branding/logo.png');

    expect($media->fresh()->disk)->toBe('uploads')
        ->and($media->fresh()->conversions_disk)->toBe('uploads')
        ->and($ticket->fresh()->photos)->toBe(['abc' => 'ticket-photos/a.jpg', 'def' => 'ticket-photos/fehlt.jpg'])
        ->and($asset->fresh()->image)->toBe('Objekt 1/Assets/Pumpe/b.jpg')
        ->and(Storage::disk('uploads')->get('ticket-photos/a.jpg'))->toBe('foto-a');
});

it('does nothing in a dry run', function () {
    loginAsAdmin();
    ['media' => $media] = seedPublicUploads();

    $this->artisan('uploads:make-private', ['--dry-run' => true])
        ->expectsOutputToContain('Dateien würden verschoben: 4')
        ->expectsOutputToContain('Medien-Einträge würden umgestellt: 1')
        ->expectsOutputToContain('Trockenlauf')
        ->assertSuccessful();

    Storage::disk('public')->assertExists('ticket-photos/a.jpg');
    Storage::disk('public')->assertExists($media->getPathRelativeToRoot());

    expect(Storage::disk('uploads')->allFiles())->toBeEmpty()
        ->and($media->fresh()->disk)->toBe('public');
});

it('is idempotent', function () {
    loginAsAdmin();
    seedPublicUploads();

    $this->artisan('uploads:make-private')->assertSuccessful();

    $this->artisan('uploads:make-private')
        ->expectsOutputToContain('Dateien verschoben: 0')
        ->expectsOutputToContain('Medien-Einträge umgestellt: 0')
        ->assertSuccessful();

    expect(Storage::disk('uploads')->exists('ticket-photos/a.jpg'))->toBeTrue();
});

it('finishes an interrupted run and reports files that differ on the target', function () {
    loginAsAdmin();
    seedPublicUploads();

    Storage::disk('uploads')->put('ticket-photos/a.jpg', 'foto-a');
    Storage::disk('uploads')->put('waste-receipts/w.pdf', 'ganz-anderer-inhalt');

    $this->artisan('uploads:make-private')
        ->expectsOutputToContain('Konflikte: 1')
        ->assertFailed();

    Storage::disk('public')->assertMissing('ticket-photos/a.jpg');
    Storage::disk('public')->assertExists('waste-receipts/w.pdf');

    expect(Storage::disk('uploads')->get('waste-receipts/w.pdf'))->toBe('ganz-anderer-inhalt');
});

it('refuses to run while the upload disk is still public', function () {
    config(['filament.default_filesystem_disk' => 'public']);

    $this->artisan('uploads:make-private')->assertFailed();
});
