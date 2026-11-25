<?php

use App\Filament\Admin\Pages\ManageAppSettings;
use App\Filament\Fm\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Fm\Resources\TicketResource\RelationManagers\RepliesRelationManager;
use App\Filament\Portal\Resources\TicketResource\RelationManagers\PublicRepliesRelationManager;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Models\Asset;
use App\Models\BimModel;
use App\Models\Property;
use App\Models\Ticket;
use App\Services\AppSettings;
use App\Support\Uploads;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;

dataset('script capable uploads', [
    'svg' => fn () => UploadedFile::fake()->createWithContent('bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>'),
    'html' => fn () => UploadedFile::fake()->createWithContent('seite.html', '<!DOCTYPE html><html><body><script>alert(document.domain)</script></body></html>'),
    'php' => fn () => UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);'),
    'js' => fn () => UploadedFile::fake()->createWithContent('skript.js', 'alert(document.domain);'),
]);

it('rejects script capable attachments in the FM ticket replies', function (UploadedFile $file) {
    loginAsAdminInFmPanel();
    Storage::fake('uploads');

    $ticket = Ticket::factory()->create();

    Livewire::test(RepliesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => EditTicket::class])
        ->callAction(TestAction::make('create')->table(), data: ['message' => 'Anhang', 'attachments' => [$file]])
        ->assertHasActionErrors(['attachments']);

    expect($ticket->replies()->count())->toBe(0);
})->with('script capable uploads');

it('rejects script capable attachments in the portal ticket replies', function (UploadedFile $file) {
    loginAsAdminInFmPanel();
    Storage::fake('uploads');

    $ticket = Ticket::factory()->create();

    Livewire::test(PublicRepliesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewTicket::class])
        ->callAction(TestAction::make('create')->table(), data: ['message' => 'Anhang', 'attachments' => [$file]])
        ->assertHasActionErrors(['attachments']);

    expect($ticket->replies()->count())->toBe(0);
})->with('script capable uploads');

it('accepts pdf and raster image attachments in ticket replies', function () {
    loginAsAdminInFmPanel();
    Storage::fake('uploads');

    $ticket = Ticket::factory()->create();

    Livewire::test(RepliesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => EditTicket::class])
        ->callAction(TestAction::make('create')->table(), data: [
            'message' => 'Anhang',
            'attachments' => [UploadedFile::fake()->create('angebot.pdf', 20, 'application/pdf'), UploadedFile::fake()->image('foto.png')],
        ])
        ->assertHasNoActionErrors();

    expect($ticket->replies()->sole()->attachments)->toHaveCount(2);
});

it('rejects script capable uploads in the documents tab', function (UploadedFile $file) {
    loginAsAdmin();
    Storage::fake('uploads');

    $asset = Asset::factory()->create();

    Livewire::test(FolderFilesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('upload')->table(), ['files' => [$file]])
        ->assertHasActionErrors(['files']);

    expect(File::query()->count())->toBe(0);
})->with('script capable uploads');

it('rejects files above 10 MB in the documents tab', function () {
    loginAsAdmin();
    Storage::fake('uploads');

    $asset = Asset::factory()->create();

    Livewire::test(FolderFilesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('upload')->table(), ['files' => [UploadedFile::fake()->create('gross.pdf', 10241, 'application/pdf')]])
        ->assertHasActionErrors(['files']);

    expect(File::query()->count())->toBe(0);
});

it('rejects svg logos and favicons in the app settings', function (string $field) {
    loginAsSuperAdminInAdminPanel();
    Storage::fake('public');

    Livewire::test(ManageAppSettings::class)
        ->fillForm([$field => UploadedFile::fake()->createWithContent('bild.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script></svg>')])
        ->call('save')
        ->assertHasFormErrors([$field]);

    expect(AppSettings::get($field))->toBeNull();
})->with(['logo_light', 'logo_dark', 'favicon']);

it('accepts png favicons in the app settings', function () {
    loginAsSuperAdminInAdminPanel();
    Storage::fake('public');

    Livewire::test(ManageAppSettings::class)
        ->fillForm(['favicon' => UploadedFile::fake()->image('favicon.png', 32, 32)])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(AppSettings::faviconUrl())->not->toBeNull();
});

it('rejects svg receipts on cost entries', function () {
    loginAsAdmin();
    Storage::fake('uploads');

    $asset = Asset::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('create')->table(), [
            'kind' => 'actual',
            'amount' => 50,
            'booked_on' => '2026-09-04',
            'receipt' => UploadedFile::fake()->createWithContent('beleg.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>1</script></svg>'),
        ])
        ->assertHasActionErrors(['receipt']);

    expect($asset->costEntries()->count())->toBe(0);
});

it('refuses tampered receipt paths on cost entries so foreign files are never imported or deleted', function () {
    loginAsAdmin();
    Storage::fake('uploads');
    Storage::disk('uploads')->put('geheim/fremd.pdf', 'vertraulich');

    $asset = Asset::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('create')->table(), [
            'kind' => 'actual',
            'amount' => 50,
            'booked_on' => '2026-09-04',
            'receipt' => ['geheim/fremd.pdf'],
        ])
        ->assertHasActionErrors(['receipt']);

    expect($asset->costEntries()->count())->toBe(0);
    Storage::disk('uploads')->assertExists('geheim/fremd.pdf');
});

it('keeps the stored receipt path when a cost entry is edited', function () {
    loginAsAdmin();
    Storage::fake('uploads');
    Storage::disk('uploads')->put('receipts/eigen.pdf', 'beleg');

    $asset = Asset::factory()->create();
    $entry = $asset->costEntries()->create(['kind' => 'actual', 'amount' => 10, 'booked_on' => '2026-09-04', 'receipt' => 'receipts/eigen.pdf']);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('edit')->table($entry), ['amount' => 60])
        ->assertHasNoActionErrors();

    expect((float) $entry->fresh()->amount)->toBe(60.0);
});

it('only accepts ifc files in the bim upload', function (UploadedFile $file) {
    requiresPlugin('bim-viewer');

    loginAsAdminInFmPanel();
    Storage::fake('local');

    $property = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->callAction('upload', data: ['file' => $file])
        ->assertHasActionErrors(['file']);

    expect(BimModel::query()->count())->toBe(0);
})->with([
    'html' => fn () => UploadedFile::fake()->createWithContent('modell.html', '<html><script>1</script></html>'),
    'svg' => fn () => UploadedFile::fake()->createWithContent('modell.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
    'txt with step content' => fn () => UploadedFile::fake()->createWithContent('modell.txt', 'ISO-10303-21;'),
]);

it('refuses tampered file paths in the bim upload', function (string $path) {
    requiresPlugin('bim-viewer');

    loginAsAdminInFmPanel();
    Storage::fake('local');
    Storage::disk('local')->put('geheim.ifc', 'ISO-10303-21;');

    $property = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->callAction('upload', data: ['file' => [$path]])
        ->assertHasActionErrors(['file']);

    expect(BimModel::query()->count())->toBe(0);
})->with(['outside folder' => 'geheim.ifc', 'traversal' => 'bim-models/../geheim.ifc', 'dotenv' => '../.env']);

it('validates upload types by file content and not by name', function (string $name, string $content, bool $allowed) {
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, $content);

    $upload = new UploadedFile($path, $name, null, null, true);

    $passes = validator(['file' => $upload], ['file' => 'mimetypes:'.implode(',', Uploads::documents())])->passes();

    unlink($path);

    expect($passes)->toBe($allowed);
})->with([
    'html named pdf' => ['rechnung.pdf', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>', false],
    'svg named png' => ['foto.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', false],
    'php named txt' => ['notiz.txt', '<?php system($_GET["c"]);', false],
    'real pdf' => ['rechnung.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", true],
    'plain text' => ['notiz.txt', 'Hallo Welt', true],
    'csv' => ['liste.csv', "a;b;c\n1;2;3\n", true],
]);

it('limits livewire temporary uploads to 50 MB and no longer previews svg', function () {
    expect(config('livewire.temporary_file_upload.rules'))->toContain('max:51200')
        ->and(config('livewire.temporary_file_upload.preview_mimes'))->not->toContain('svg');
});
