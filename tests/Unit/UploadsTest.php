<?php

use App\Support\Uploads;

it('never allows script-capable types for any upload kind', function (string $kind) {
    $types = Uploads::{$kind}();

    expect($types)->not->toContain('image/svg+xml')
        ->and($types)->not->toContain('text/html')
        ->and($types)->not->toContain('application/xhtml+xml')
        ->and($types)->not->toContain('text/xml')
        ->and($types)->not->toContain('application/xml')
        ->and($types)->not->toContain('application/javascript')
        ->and($types)->not->toContain('text/javascript')
        ->and($types)->not->toContain('application/x-httpd-php')
        ->and($types)->not->toContain('image/*');
})->with(['documents', 'images', 'icons', 'receipts', 'ifc']);

it('offers raster images only for image uploads', function () {
    expect(Uploads::images())->toBe(['image/png', 'image/jpeg', 'image/webp', 'image/gif']);
});

it('offers png and ico only for icons', function () {
    expect(Uploads::icons())->toBe(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon']);
});

it('offers office documents, pdf, text and raster images for documents', function () {
    expect(Uploads::documents())->toContain(
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'text/plain',
    );
});

it('offers pdf and raster images for receipts', function () {
    expect(Uploads::receipts())->toBe(['application/pdf', ...Uploads::images()]);
});
