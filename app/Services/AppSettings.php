<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AppSettings
{
    public const CACHE_KEY = 'app-settings';

    public const DISK = 'public';

    public const DIRECTORY = 'branding';

    /**
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'app_name' => 'Open-FM',
        'logo_light' => null,
        'logo_dark' => null,
        'favicon' => null,
        'logo_height' => '2rem',
        'mail_from_name' => null,
        'mail_from_address' => null,
    ];

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('app_settings')) {
                    return [];
                }

                return AppSetting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        return [...self::DEFAULTS, ...$stored];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function logoUrl(string $variant = 'light'): ?string
    {
        return self::fileUrl(self::get($variant === 'dark' ? 'logo_dark' : 'logo_light'));
    }

    public static function faviconUrl(): ?string
    {
        return self::fileUrl(self::get('favicon'));
    }

    public static function brandLogo(string $variant = 'light'): string
    {
        return self::logoUrl($variant)
            ?? ($variant === 'dark' ? self::logoUrl('light') : null)
            ?? asset('assets/logo/logo-'.$variant.'.png');
    }

    public static function brandFavicon(): string
    {
        return self::faviconUrl() ?? asset('assets/logo/favicon.png');
    }

    public static function logoHeight(): string
    {
        return (string) (self::get('logo_height') ?: self::DEFAULTS['logo_height']);
    }

    public static function apply(): void
    {
        $settings = self::all();

        if (filled($settings['app_name'])) {
            config(['app.name' => $settings['app_name']]);
        }

        if (filled($settings['mail_from_address'])) {
            config(['mail.from.address' => $settings['mail_from_address']]);
        }

        if (filled($settings['mail_from_name'])) {
            config(['mail.from.name' => $settings['mail_from_name']]);
        }
    }

    protected static function fileUrl(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}
