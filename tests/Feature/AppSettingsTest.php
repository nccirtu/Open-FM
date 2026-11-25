<?php

use App\Filament\Admin\Pages\ManageAppSettings;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\AppSettings;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders the app settings page with its sections for admins', function () {
    loginAsSuperAdminInAdminPanel();

    Livewire::test(ManageAppSettings::class)
        ->assertOk()
        ->assertSee(__('settings.sections.general'))
        ->assertSee(__('settings.sections.branding'))
        ->assertSee(__('settings.sections.mail'))
        ->assertSchemaStateSet(['app_name' => AppSettings::DEFAULTS['app_name'], 'logo_height' => '2rem']);

    expect(collect(Filament::getPanel('admin')->getPages())->contains(ManageAppSettings::class))->toBeTrue();
});

it('saves settings, uploads the logo and applies name and mail sender', function () {
    loginAsSuperAdminInAdminPanel();
    Storage::fake('public');

    Livewire::test(ManageAppSettings::class)
        ->fillForm([
            'app_name' => 'Facility Suite',
            'logo_light' => UploadedFile::fake()->image('logo.png', 300, 80),
            'logo_height' => '40px',
            'mail_from_name' => 'Facility Suite',
            'mail_from_address' => 'noreply@facility.test',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(__('settings.saved'));

    expect(AppSettings::get('app_name'))->toBe('Facility Suite')
        ->and(AppSettings::logoHeight())->toBe('40px')
        ->and(AppSettings::logoUrl('light'))->toContain('/storage/branding/')
        ->and(AppSettings::logoUrl('dark'))->toBeNull()
        ->and(config('app.name'))->toBe('Facility Suite')
        ->and(config('mail.from.address'))->toBe('noreply@facility.test')
        ->and(AppSetting::query()->count())->toBe(7);

    Storage::disk('public')->assertExists(AppSettings::get('logo_light'));

    $panel = Filament::getPanel('fm');

    expect($panel->getBrandLogo())->toBe(AppSettings::logoUrl('light'))
        ->and($panel->getDarkModeBrandLogo())->toBe(AppSettings::logoUrl('light'))
        ->and($panel->getBrandLogoHeight())->toBe('40px');
});

it('validates the logo height and sender address', function () {
    loginAsSuperAdminInAdminPanel();

    Livewire::test(ManageAppSettings::class)
        ->fillForm(['logo_height' => 'gross', 'mail_from_address' => 'keine-adresse'])
        ->call('save')
        ->assertHasFormErrors(['logo_height', 'mail_from_address']);
});

it('hides the settings page from everyone but super admins', function () {
    $admin = loginAsAdminInAdminPanel();

    expect(ManageAppSettings::canAccess())->toBeFalse();

    $admin->forceFill(['is_super_admin' => true])->save();

    expect(ManageAppSettings::canAccess())->toBeTrue();

    $this->actingAs(User::factory()->create());

    expect(ManageAppSettings::canAccess())->toBeFalse();
});
