<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

final class Uploads
{
    public const DISK = 'uploads';

    public const URL_MINUTES = 30;

    public const IMAGES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
    ];

    public const ICONS = [
        'image/png',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ];

    public const DOCUMENTS = [
        'application/pdf',
        ...self::IMAGES,
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    ];

    public const RECEIPTS = [
        'application/pdf',
        ...self::IMAGES,
    ];

    public const IFC = [
        'application/octet-stream',
        'text/plain',
        'application/x-step',
        'model/ifc',
    ];

    /**
     * @return array<int, string>
     */
    public static function documents(): array
    {
        return self::DOCUMENTS;
    }

    /**
     * @return array<int, string>
     */
    public static function images(): array
    {
        return self::IMAGES;
    }

    /**
     * @return array<int, string>
     */
    public static function icons(): array
    {
        return self::ICONS;
    }

    /**
     * @return array<int, string>
     */
    public static function receipts(): array
    {
        return self::RECEIPTS;
    }

    /**
     * @return array<int, string>
     */
    public static function ifc(): array
    {
        return self::IFC;
    }

    public static function disk(): string
    {
        return (string) config('filament.default_filesystem_disk', self::DISK);
    }

    public static function mediaDisk(): string
    {
        return (string) config('media-library.disk_name', self::DISK);
    }

    public static function receiptDisk(): string
    {
        return (string) config('receipt-capture.disk', self::mediaDisk());
    }

    public static function url(?string $path, ?string $disk = null, ?int $minutes = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        $storage = Storage::disk($disk ?? self::disk());

        try {
            return $storage->temporaryUrl($path, now()->addMinutes($minutes ?? self::URL_MINUTES));
        } catch (Throwable) {
            return $storage->url($path);
        }
    }
}
