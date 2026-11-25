<?php

use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Integrations\ReceiptCapture\SignedReceiptPreviewAction;
use App\Filament\Portal\Resources\TicketResource\RelationManagers\PublicRepliesRelationManager;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Http\Resources\Api\V1\AssetResource as AssetApiResource;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Property;
use App\Models\Ticket;
use App\Support\Uploads;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File as MediaFile;

function useRealUploadsDisk(): string
{
    $root = sys_get_temp_dir().'/open-fm-uploads-'.Str::random(10);

    register_shutdown_function(fn () => File::deleteDirectory($root));

    config(['filesystems.disks.uploads.root' => $root]);
    Storage::forgetDisk('uploads');

    return $root;
}

it('configures a private served uploads disk and points all upload defaults at it', function () {
    expect(config('filesystems.disks.uploads'))
        ->toMatchArray(['driver' => 'local', 'visibility' => 'private', 'serve' => true, 'throw' => false])
        ->and(config('filesystems.disks.uploads.root'))->toBe(storage_path('app/private/uploads'))
        ->and(config('filament.default_filesystem_disk'))->toBe('uploads')
        ->and(config('media-library.disk_name'))->toBe('uploads')
        ->and(config('media-library.max_file_size'))->toBe(10 * 1024 * 1024)
        ->and(config('receipt-capture.disk'))->toBe('uploads')
        ->and(config('form-builder.uploads.disk'))->toBe('uploads')
        ->and(Uploads::disk())->toBe('uploads')
        ->and(Uploads::mediaDisk())->toBe('uploads')
        ->and(Uploads::receiptDisk())->toBe('uploads')
        ->and(Route::getRoutes()->getByName('storage.uploads')?->uri())->toBe('uploads/{path}');
});

it('stores portal reply attachments on the uploads disk and never on public', function () {
    loginAsAdminInFmPanel();
    Storage::fake('public');

    $ticket = Ticket::factory()->create();

    Livewire::test(PublicRepliesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewTicket::class])
        ->callAction(TestAction::make('create')->table(), data: [
            'message' => 'Anhang',
            'attachments' => [UploadedFile::fake()->create('angebot.pdf', 20, 'application/pdf')],
        ])
        ->assertHasNoActionErrors();

    $attachments = $ticket->replies()->sole()->attachments;

    expect($attachments)->toHaveCount(1);

    foreach ($attachments as $path) {
        expect($path)->toStartWith('ticket-attachments/');
        Storage::disk('uploads')->assertExists($path);
    }

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('stores cost entry receipts on the uploads disk and never on public', function () {
    loginAsAdmin();
    Storage::fake('public');

    $asset = Asset::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('create')->table(), [
            'kind' => 'actual',
            'amount' => 50,
            'booked_on' => '2026-09-04',
            'receipt' => UploadedFile::fake()->create('beleg.pdf', 5, 'application/pdf'),
        ])
        ->assertHasNoActionErrors();

    $entry = $asset->costEntries()->sole();

    Storage::disk('uploads')->assertExists($entry->receipt);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('stores asset photos on the uploads disk and never on public', function () {
    loginAsAdmin();
    Storage::fake('public');

    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $type = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    Livewire::test(CreateAsset::class)
        ->fillForm([
            'property_id' => $property->id,
            'name' => 'Heizung mit Bild',
            'asset_type_id' => $type->id,
            'image' => UploadedFile::fake()->image('heizung.jpg'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->where('name', 'Heizung mit Bild')->firstOrFail();

    Storage::disk('uploads')->assertExists($asset->image);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('stores documents tab uploads of the media manager on the uploads disk', function () {
    loginAsAdmin();
    Storage::fake('public');

    $asset = Asset::factory()->create(['name' => 'Heizung']);

    Livewire::test(FolderFilesRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->callAction(TestAction::make('upload')->table(), ['files' => [UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')]])
        ->assertHasNoActionErrors();

    $media = MediaFile::query()->sole()->getFirstMedia('default');

    expect($media->disk)->toBe('uploads');

    Storage::disk('uploads')->assertExists($media->getPathRelativeToRoot());

    expect(Storage::disk('public')->allFiles())->toBeEmpty()
        ->and(MediaFile::query()->sole()->getUrl())->not->toBeNull();
});

it('creates signed and expiring urls for the preview of uploaded files in the form', function () {
    loginAsAdmin();
    $root = useRealUploadsDisk();

    $asset = Asset::factory()->create(['image' => 'assets/pumpe.png']);

    File::ensureDirectoryExists($root.'/assets');
    File::put($root.'/assets/pumpe.png', UploadedFile::fake()->image('pumpe.png')->get());

    $url = null;

    Livewire::test(EditAsset::class, ['record' => $asset->getKey()])
        ->assertFormComponentExists('image', function (FileUpload $component) use ($asset, &$url): bool {
            $url = $component->getUploadedFile($asset->image, null)['url'];

            return true;
        });

    expect($url)->toContain('/uploads/assets/pumpe.png')
        ->and($url)->toContain('expires=')
        ->and($url)->toContain('signature=');

    $this->get($url)->assertOk();

    $this->get(Str::before($url, '?'))->assertForbidden();

    $this->travel(2)->hours();

    $this->get($url)->assertForbidden();
});

it('hands out temporary urls through the uploads helper', function () {
    useRealUploadsDisk();
    Storage::disk('uploads')->put('ticket-photos/foto.png', 'png');

    $url = Uploads::url('ticket-photos/foto.png');

    expect($url)->toContain('/uploads/ticket-photos/foto.png')
        ->and($url)->toContain('signature=')
        ->and(Uploads::url(null))->toBeNull()
        ->and(Uploads::url(''))->toBeNull();

    $this->get($url)->assertOk();
});

it('exposes the asset image in the api as an expiring signed url', function () {
    useRealUploadsDisk();
    Storage::disk('uploads')->put('assets/pumpe.png', 'png');

    $asset = Asset::factory()->create(['image' => 'assets/pumpe.png']);

    $payload = (new AssetApiResource($asset))->toArray(Request::create('/'));

    expect($payload['image'])->toContain('/uploads/assets/pumpe.png')
        ->and($payload['image'])->toContain('signature=');
});

it('previews stored receipts through a signed url', function () {
    requiresPlugin('receipt-capture');

    useRealUploadsDisk();
    Storage::disk('uploads')->put('receipts/beleg.pdf', '%PDF-1.4');

    $url = SignedReceiptPreviewAction::make()->path('receipts/beleg.pdf')->getReceiptUrl();

    expect($url)->toContain('/uploads/receipts/beleg.pdf')
        ->and($url)->toContain('signature=');
});

it('refuses unsigned and tampered requests for stored files', function () {
    useRealUploadsDisk();
    Storage::disk('uploads')->put('ticket-photos/foto.png', 'png');

    $url = Uploads::url('ticket-photos/foto.png');

    $this->get('/uploads/ticket-photos/foto.png')->assertForbidden();
    $this->get(str_replace('foto.png', 'anderes.png', $url))->assertForbidden();
    $this->get($url)->assertOk();
});

it('serves active content as download and never inline', function (string $name, string $content) {
    useRealUploadsDisk();
    Storage::disk('uploads')->put("ticket-attachments/{$name}", $content);

    $response = $this->get(Uploads::url("ticket-attachments/{$name}"))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
})->with([
    'html' => ['seite.html', '<!DOCTYPE html><html><body><script>alert(document.domain)</script></body></html>'],
    'svg' => ['bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>'],
    'xhtml' => ['seite.xhtml', '<?xml version="1.0"?><html xmlns="http://www.w3.org/1999/xhtml"><body><script>alert(1)</script></body></html>'],
    'xml' => ['daten.xml', '<?xml version="1.0"?><root><a>1</a></root>'],
    'html with a harmless name' => ['seite.txt.html', '<html><script>alert(1)</script></html>'],
]);

it('keeps pdfs and images inline', function (string $name, string $content) {
    useRealUploadsDisk();
    Storage::disk('uploads')->put("ticket-attachments/{$name}", $content);

    $response = $this->get(Uploads::url("ticket-attachments/{$name}"))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
})->with([
    'png' => ['foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==')],
    'pdf' => ['rechnung.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"],
]);

it('keeps the download name of active content', function () {
    useRealUploadsDisk();
    Storage::disk('uploads')->put('ticket-attachments/Bericht März.html', '<html></html>');

    $response = $this->get(Uploads::url('ticket-attachments/Bericht März.html'))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')->toContain('filename=');
});
