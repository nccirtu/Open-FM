<?php

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Filament\Admin\Pages\ManagePlugins;
use App\Jobs\ProcessPluginOperation;
use App\Models\Plugin;
use App\Services\AppSettings;
use App\Support\Plugins\Installer\InvalidPluginArchive;
use App\Support\Plugins\Installer\PluginArchive;
use App\Support\Plugins\Installer\PluginInstaller;
use App\Support\Plugins\Installer\PluginSignature;
use App\Support\Plugins\Marketplace\MarketplaceClient;
use App\Support\Plugins\Marketplace\MarketplaceException;
use App\Support\Plugins\PluginManager;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->pluginTmp = storage_path('framework/testing/plugins-'.Str::random(8));
    File::ensureDirectoryExists($this->pluginTmp);

    File::ensureDirectoryExists($this->pluginTmp.'/project');
    File::copy(base_path('composer.json'), $this->pluginTmp.'/project/composer.json');
    File::copy(base_path('composer.lock'), $this->pluginTmp.'/project/composer.lock');

    config([
        'plugins.artifacts_path' => $this->pluginTmp.'/artifacts',
        'plugins.composer.binary' => 'composer',
        'plugins.composer.working_dir' => $this->pluginTmp.'/project',
        'plugins.dev_path' => null,
        'plugins.public_key' => null,
        'plugins.require_signature' => false,
        'plugins.marketplace.url' => null,
    ]);
});

afterEach(function (): void {
    File::deleteDirectory($this->pluginTmp);
});

/**
 * @param  array<string, mixed>  $composer
 */
function pluginZip(array $composer, string $directory = ''): string
{
    $path = test()->pluginTmp.'/'.Str::random(8).'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString($directory.'composer.json', json_encode($composer));
    $zip->addFromString($directory.'src/Plugin.php', '<?php');
    $zip->close();

    return $path;
}

function commandOf(PendingProcess $process): string
{
    return implode(' ', (array) $process->command);
}

/**
 * @return array<string, mixed>
 */
function pluginComposerJson(): array
{
    return json_decode(File::get(test()->pluginTmp.'/project/composer.plugins.json'), true);
}

function fakeMissingPluginRecord(): void
{
    config(['plugins.catalog.demo-missing' => ['package' => 'vendor/does-not-exist', 'category' => 'structure', 'icon' => 'heroicon-o-cube']]);
    Plugin::factory()->create(['slug' => 'demo-missing', 'package' => 'vendor/does-not-exist', 'installed_at' => now()]);
}

it('reads package, version and slug from a plugin zip', function () {
    $archive = PluginArchive::open(pluginZip(['name' => 'cafm/qr-code', 'version' => '1.2.0'], 'qr-code/'), app(PluginManager::class));

    expect($archive->package)->toBe('cafm/qr-code')
        ->and($archive->version)->toBe('1.2.0')
        ->and($archive->slug)->toBe('qr-code')
        ->and($archive->artifactFileName())->toBe('cafm-qr-code-1.2.0.zip');

    $thirdParty = PluginArchive::open(pluginZip(['name' => 'acme/energy', 'version' => '0.1.0', 'extra' => ['open-fm' => ['slug' => 'energy', 'name' => 'Energie']]]), app(PluginManager::class));

    expect($thirdParty->slug)->toBe('energy')
        ->and($thirdParty->manifest['name'])->toBe('Energie');
});

it('rejects archives that cannot be installed', function (Closure $zip, string $message) {
    expect(fn () => PluginArchive::open($zip(), app(PluginManager::class)))
        ->toThrow(InvalidPluginArchive::class, __($message, ['slug' => 'qr-code']));
})->with([
    'not a zip' => [fn (): string => tap(test()->pluginTmp.'/broken.zip', fn (string $path) => File::put($path, 'kein zip')), 'plugins.installer.errors.not_a_zip'],
    'no composer.json' => [function (): string {
        $path = test()->pluginTmp.'/empty.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('README.md', '#');
        $zip->close();

        return $path;
    }, 'plugins.installer.errors.missing_composer_json'],
    'no version' => [fn (): string => pluginZip(['name' => 'cafm/qr-code']), 'plugins.installer.errors.missing_version'],
    'not an open-fm plugin' => [fn (): string => pluginZip(['name' => 'acme/energy', 'version' => '1.0.0']), 'plugins.installer.errors.not_a_plugin'],
    'catalog slug of another package' => [fn (): string => pluginZip(['name' => 'acme/qr', 'version' => '1.0.0', 'extra' => ['open-fm' => ['slug' => 'qr-code']]]), 'plugins.installer.errors.slug_taken'],
]);

it('signs and verifies plugin zips with ed25519', function () {
    $keys = PluginSignature::generateKeyPair();
    $zip = pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0']);
    $signature = PluginSignature::sign($zip, $keys['secret']);

    expect(PluginSignature::verify($zip, $signature, $keys['public']))->toBeTrue()
        ->and(PluginSignature::verify($zip, $signature, PluginSignature::generateKeyPair()['public']))->toBeFalse()
        ->and(PluginSignature::verify($zip, 'kaputt', $keys['public']))->toBeFalse();

    File::append($zip, 'manipuliert');

    expect(PluginSignature::verify($zip, $signature, $keys['public']))->toBeFalse();
});

it('requires a valid signature or an explicit trust confirmation for uploads', function () {
    $installer = app(PluginInstaller::class);
    $zip = pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0']);

    expect(fn () => $installer->stageUpload($zip))->toThrow(InvalidPluginArchive::class, __('plugins.installer.errors.unsigned'));

    [$plugin, $version] = $installer->stageUpload($zip, trustUnsigned: true);

    expect($plugin->slug)->toBe('qr-code')
        ->and($plugin->source)->toBe(PluginSource::Upload)
        ->and($version)->toBe('1.0.0')
        ->and(File::exists(config('plugins.artifacts_path').'/cafm-qr-code-1.0.0.zip'))->toBeTrue();

    $keys = PluginSignature::generateKeyPair();
    config(['plugins.public_key' => $keys['public'], 'plugins.require_signature' => true]);

    expect(fn () => $installer->stageUpload($zip, trustUnsigned: true))->toThrow(InvalidPluginArchive::class)
        ->and(fn () => $installer->stageUpload($zip, 'falsch'))->toThrow(InvalidPluginArchive::class, __('plugins.installer.errors.invalid_signature'))
        ->and($installer->stageUpload($zip, PluginSignature::sign($zip, $keys['secret']))[0]->slug)->toBe('qr-code');
});

it('installs a plugin from the artifact repository and runs the follow-up commands', function () {
    Process::fake();
    $installer = app(PluginInstaller::class);
    [$plugin, $version] = $installer->stageUpload(pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0']), trustUnsigned: true);

    $installer->dispatch($plugin, 'install', $version, sync: true);

    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'update cafm/qr-code --with-dependencies')
        && $process->environment['COMPOSER'] === 'composer.plugins.json'
        && $process->path === $this->pluginTmp.'/project');

    expect(pluginComposerJson()['require']['cafm/qr-code'])->toBe('1.0.0')
        ->and(pluginComposerJson()['repositories'][0])->toBe(['type' => 'artifact', 'url' => config('plugins.artifacts_path')])
        ->and(File::get($this->pluginTmp.'/project/composer.plugins.lock'))->toBe(File::get(base_path('composer.lock')))
        ->and(File::get($this->pluginTmp.'/project/composer.json'))->toBe(File::get(base_path('composer.json')));
    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'artisan migrate --force'));
    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'artisan filament:assets'));
    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'artisan plugins:sync'));
    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'artisan queue:restart'));

    expect($plugin->refresh()->status)->toBe(PluginStatus::Installed)
        ->and($plugin->installed_at)->not->toBeNull()
        ->and($plugin->last_error)->toBeNull();
});

it('marks the plugin as failed and restores the plugin composer file when composer fails', function () {
    $composerJson = File::get($this->pluginTmp.'/project/composer.json');
    File::put($this->pluginTmp.'/project/composer.plugins.json', '{"vorher": true}');
    Process::fake([
        '*update*cafm/qr-code*' => Process::result(errorOutput: 'Your requirements could not be resolved', exitCode: 2),
        '*' => Process::result(),
    ]);
    $installer = app(PluginInstaller::class);
    [$plugin, $version] = $installer->stageUpload(pluginZip(['name' => 'cafm/qr-code', 'version' => '9.9.9']), trustUnsigned: true);

    $installer->dispatch($plugin, 'install', $version, sync: true);

    expect($plugin->refresh()->status)->toBe(PluginStatus::Failed)
        ->and($plugin->last_error)->toContain('could not be resolved')
        ->and(File::get($this->pluginTmp.'/project/composer.json'))->toBe($composerJson)
        ->and(File::get($this->pluginTmp.'/project/composer.plugins.json'))->toBe('{"vorher": true}')
        ->and($plugin->installed_at)->toBeNull();

    Process::assertDidntRun(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'artisan migrate'));
});

it('removes and updates plugins via composer', function () {
    Process::fake();
    $plugin = Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'source' => PluginSource::Upload]);
    $installer = app(PluginInstaller::class);

    $plugin->update(['installed_at' => now(), 'version' => '1.0.0']);

    $installer->dispatch($plugin, 'update', sync: true);
    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'update cafm/qr-code --with-dependencies'));
    expect(pluginComposerJson()['require']['cafm/qr-code'])->toBe('1.0.0');

    $installer->dispatch($plugin, 'remove', sync: true);
    Process::assertRan(fn (PendingProcess $process): bool => commandOf($process) === 'composer update cafm/qr-code --no-interaction --no-ansi');

    expect(pluginComposerJson()['require'])->not->toHaveKey('cafm/qr-code')
        ->and($plugin->refresh()->status)->toBe(PluginStatus::Installed)
        ->and($plugin->installed_at)->toBeNull();
});

it('queues plugin operations in the background', function () {
    Queue::fake();
    $plugin = Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code']);

    app(PluginInstaller::class)->dispatch($plugin, 'update');

    Queue::assertPushed(ProcessPluginOperation::class, fn (ProcessPluginOperation $job): bool => $job->slug === 'qr-code' && $job->operation === 'update');
    expect($plugin->refresh()->status)->toBe(PluginStatus::Updating);
});

it('rejects package names from the shop that are not composer packages', function () {
    config(['plugins.marketplace.url' => 'https://shop.open-fm.test']);
    Http::fake([
        'shop.open-fm.test/api/v1/plugins' => Http::response(['data' => [
            ['slug' => 'qr-code', 'package' => '--working-dir=/tmp', 'version' => '1.0.0'],
        ]]),
        'shop.open-fm.test/api/v1/licenses' => Http::response(['license_key' => 'LIZENZ-123', 'repository_token' => 'TOKEN-456']),
    ]);

    expect(fn () => app(PluginInstaller::class)->stageMarketplace('qr-code'))->toThrow(InvalidPluginArchive::class);
    expect(Plugin::query()->where('slug', 'qr-code')->exists())->toBeFalse();
});

it('refuses a shop url without https outside local and testing environments', function () {
    config(['plugins.marketplace.url' => 'http://shop.open-fm.test']);
    app()->detectEnvironment(fn (): string => 'production');
    Http::fake();

    expect(app(MarketplaceClient::class)->catalog())->toBeEmpty();

    Http::assertNothingSent();
});

it('reads the shop catalog and issues the licence for this installation', function () {
    config(['plugins.marketplace.url' => 'https://shop.open-fm.test']);
    Http::fake([
        'shop.open-fm.test/api/v1/plugins' => Http::response(['data' => [
            ['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'version' => '1.0.0', 'price' => ['amount' => 0, 'currency' => 'EUR']],
        ]]),
        'shop.open-fm.test/api/v1/licenses' => Http::response(['license_key' => 'LIZENZ-123', 'repository_token' => 'TOKEN-456']),
    ]);

    $marketplace = app(MarketplaceClient::class);

    expect($marketplace->catalog()->keys()->all())->toBe(['qr-code'])
        ->and($marketplace->repositoryUrl())->toBe('https://shop.open-fm.test/composer');

    $plugin = app(PluginInstaller::class)->stageMarketplace('qr-code');

    expect($plugin->source)->toBe(PluginSource::Marketplace)
        ->and($plugin->license_key)->toBe('LIZENZ-123')
        ->and($plugin->getRawOriginal('license_key'))->not->toBe('LIZENZ-123')
        ->and($marketplace->repositoryToken())->toBe('TOKEN-456');

    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/v1/licenses')
        && $request['installation_id'] === MarketplaceClient::installationId()
        && $request['plugin'] === 'qr-code');
});

it('uses the shop repository with the installation token only in memory', function () {
    config(['plugins.marketplace.url' => 'https://shop.open-fm.test']);
    AppSettings::set(['marketplace_repository_token' => Crypt::encryptString('TOKEN-456')]);
    Process::fake();
    $plugin = Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'source' => PluginSource::Marketplace, 'license_key' => 'LIZENZ-123']);

    app(PluginInstaller::class)->dispatch($plugin, 'install', sync: true);

    Process::assertRan(function (PendingProcess $process): bool {
        $auth = json_decode($process->environment['COMPOSER_AUTH'] ?? '{}', true);

        return str_contains(commandOf($process), 'update cafm/qr-code --with-dependencies')
            && $auth['http-basic']['shop.open-fm.test'] === ['username' => MarketplaceClient::installationId(), 'password' => 'TOKEN-456'];
    });

    expect(pluginComposerJson()['require']['cafm/qr-code'])->toBe('*')
        ->and(pluginComposerJson()['repositories'])->toContain(['type' => 'composer', 'url' => 'https://shop.open-fm.test/composer'])
        ->and(File::exists($this->pluginTmp.'/project/auth.json'))->toBeFalse();
});

it('restores registered plugins after a core update replaced the project lock', function () {
    Process::fake();
    $installer = app(PluginInstaller::class);
    Plugin::factory()->create(['slug' => 'alpha', 'package' => 'acme/alpha', 'source' => PluginSource::Upload, 'version' => '1.0.0', 'installed_at' => now()]);
    Plugin::factory()->create(['slug' => 'beta', 'package' => 'acme/beta', 'source' => PluginSource::Marketplace, 'installed_at' => now()]);
    Plugin::factory()->create(['slug' => 'gamma', 'package' => 'acme/gamma', 'installed_at' => null]);

    File::put($this->pluginTmp.'/project/composer.lock', '{"core": "neu"}');

    $installer->dispatchRestore(sync: true);

    Process::assertRan(fn (PendingProcess $process): bool => str_contains(commandOf($process), 'update acme/alpha acme/beta --with-dependencies'));

    expect(pluginComposerJson()['require'])->toMatchArray(['acme/alpha' => '1.0.0', 'acme/beta' => '*'])
        ->and(pluginComposerJson()['require'])->not->toHaveKey('acme/gamma')
        ->and(File::get($this->pluginTmp.'/project/composer.plugins.lock'))->toBe('{"core": "neu"}')
        ->and(Plugin::query()->where('slug', 'alpha')->first()->status)->toBe(PluginStatus::Installed);
});

it('lists registered plugins whose packages are missing', function () {
    fakeMissingPluginRecord();

    expect(app(PluginInstaller::class)->missingPlugins())->toBe(['demo-missing']);
});

it('installs plugins from a local development directory as symlinks', function () {
    Process::fake();
    config(['plugins.dev_path' => '../Open-FM-Plugins/packages']);
    $plugin = Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'source' => PluginSource::Upload]);

    app(PluginInstaller::class)->dispatch($plugin, 'install', '1.0.0', sync: true);

    expect(pluginComposerJson()['require']['cafm/qr-code'])->toBe('*@dev')
        ->and(pluginComposerJson()['repositories'][0])->toBe(['type' => 'path', 'url' => '../Open-FM-Plugins/packages/*', 'options' => ['symlink' => true]]);
});

it('reports an unreachable shop', function () {
    config(['plugins.marketplace.url' => 'https://shop.open-fm.test']);
    Http::fake(['*' => Http::response(status: 500)]);

    expect(app(MarketplaceClient::class)->catalog())->toBeEmpty()
        ->and(fn () => app(PluginInstaller::class)->stageMarketplace('qr-code'))->toThrow(MarketplaceException::class);
});

it('uploads a plugin zip in the plugin management and queues the installation', function () {
    Queue::fake();
    Storage::fake('local');
    loginAsAdmin('super@example.com')->forceFill(['is_super_admin' => true])->save();
    Filament::setTenant(null);
    Filament::setCurrentPanel('admin');

    $zip = UploadedFile::fake()->createWithContent('qr-code.zip', File::get(pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0'])));

    Livewire::test(ManagePlugins::class)
        ->callAction('upload', data: ['zip' => $zip, 'trust_unsigned' => true])
        ->assertHasNoActionErrors();

    Queue::assertPushed(ProcessPluginOperation::class, fn (ProcessPluginOperation $job): bool => $job->slug === 'qr-code' && $job->version === '1.0.0');
});

it('offers shop installation only with a configured shop and shows the price', function () {
    requiresPlugin('qr-code');
    config(['plugins.marketplace.url' => 'https://shop.open-fm.test']);
    Http::fake(['shop.open-fm.test/api/v1/plugins' => Http::response(['data' => [
        ['slug' => 'demo-missing', 'package' => 'vendor/does-not-exist', 'price' => ['amount' => 0, 'currency' => 'EUR']],
    ]])]);
    config(['plugins.catalog.demo-missing' => ['package' => 'vendor/does-not-exist', 'category' => 'structure', 'icon' => 'heroicon-o-cube']]);
    loginAsAdmin('super@example.com')->forceFill(['is_super_admin' => true])->save();
    Filament::setTenant(null);
    Filament::setCurrentPanel('admin');

    Livewire::test(ManagePlugins::class)
        ->assertTableActionVisible('install', 'demo-missing')
        ->assertTableActionHidden('install', 'qr-code')
        ->assertTableActionVisible('remove', 'qr-code')
        ->assertSee(Number::currency(0, 'EUR', app()->getLocale()));

    config(['plugins.marketplace.url' => null]);

    Livewire::test(ManagePlugins::class)->assertTableActionHidden('install', 'demo-missing');
});

it('packs a plugin directory into a versioned, signed zip', function () {
    $source = $this->pluginTmp.'/source';
    File::ensureDirectoryExists($source.'/src');
    File::ensureDirectoryExists($source.'/node_modules/x');
    File::put($source.'/composer.json', json_encode(['name' => 'cafm/qr-code']));
    File::put($source.'/src/Plugin.php', '<?php');
    File::put($source.'/node_modules/x/index.js', '');
    $keys = PluginSignature::generateKeyPair();

    $this->artisan('plugins:pack', ['path' => $source, '--package-version' => '2.0.0', '--output' => $this->pluginTmp.'/dist', '--key' => $keys['secret']])
        ->assertSuccessful();

    $zipPath = $this->pluginTmp.'/dist/cafm-qr-code-2.0.0.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath);

    expect(json_decode($zip->getFromName('composer.json'), true)['version'])->toBe('2.0.0')
        ->and($zip->locateName('src/Plugin.php'))->not->toBeFalse()
        ->and($zip->locateName('node_modules/x/index.js'))->toBeFalse()
        ->and(PluginSignature::verify($zipPath, File::get($zipPath.'.sig'), $keys['public']))->toBeTrue();

    $zip->close();
});

it('installs a zip from the console', function () {
    Process::fake();

    $this->artisan('plugins:install', ['--zip' => pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0'])])->assertFailed();

    $this->artisan('plugins:install', ['--zip' => pluginZip(['name' => 'cafm/qr-code', 'version' => '1.0.0']), '--trust-unsigned' => true])
        ->assertSuccessful();

    expect(Plugin::query()->where('slug', 'qr-code')->first()->status)->toBe(PluginStatus::Installed);
    $this->artisan('plugins:keygen')->assertSuccessful();
});
