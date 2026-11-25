<?php

namespace App\Console\Commands;

use App\Support\Uploads;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

class MakeUploadsPrivate extends Command
{
    protected $signature = 'uploads:make-private {--dry-run : Nur anzeigen, was verschoben würde}';

    protected $description = 'Verschiebt Benutzer-Uploads von der öffentlichen Platte (public) auf die private Platte (uploads)';

    private const SOURCE_DISK = 'public';

    private const DIRECTORIES = [
        'ticket-photos',
        'ticket-attachments',
        'maintenance-protocols',
        'project-receipts',
        'waste-receipts',
        'waste-evidence',
        'meter-readings',
        'assets',
    ];

    private const COLUMNS = [
        ['assets', 'image'],
        ['meter_readings', 'photo'],
        ['tickets', 'photos'],
        ['ticket_replies', 'attachments'],
        ['maintenance_orders', 'protocol_files'],
        ['waste_evidence', 'attachments'],
        ['waste_collections', 'receipt'],
        ['cost_entries', 'receipt'],
    ];

    private bool $dryRun = false;

    /**
     * @var array{moved: int, already: int, missing: int, conflict: int, failed: int}
     */
    private array $stats = ['moved' => 0, 'already' => 0, 'missing' => 0, 'conflict' => 0, 'failed' => 0];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->stats = ['moved' => 0, 'already' => 0, 'missing' => 0, 'conflict' => 0, 'failed' => 0];

        $fieldDisk = Uploads::disk();
        $mediaDisk = Uploads::mediaDisk();

        if ($fieldDisk === self::SOURCE_DISK) {
            $this->error('Die Upload-Platte ist noch "public" (FILAMENT_FILESYSTEM_DISK). Bitte zuerst auf "uploads" stellen.');

            return self::FAILURE;
        }

        foreach ($this->fieldPaths() as $path) {
            $this->transfer($path, $fieldDisk);
        }

        $mediaUpdated = $mediaDisk === self::SOURCE_DISK ? $this->skipMedia() : $this->moveMedia($mediaDisk);

        $this->report($mediaUpdated);

        return $this->stats['conflict'] + $this->stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function fieldPaths(): array
    {
        $source = Storage::disk(self::SOURCE_DISK);
        $directories = [
            ...self::DIRECTORIES,
            (string) config('receipt-capture.directory', 'receipts'),
            (string) config('form-builder.uploads.directory', 'form-builder'),
        ];
        $paths = [];

        foreach (array_unique($directories) as $directory) {
            array_push($paths, ...$source->allFiles($directory));
        }

        foreach (self::COLUMNS as [$table, $column]) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            foreach (DB::table($table)->whereNotNull($column)->pluck($column) as $value) {
                array_push($paths, ...$this->pathsIn($value));
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    private function pathsIn(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = str_starts_with($value, '[') || str_starts_with($value, '{') ? json_decode($value, true) : [$value];

        return collect(is_array($decoded) ? $decoded : [])
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '' && ! str_contains($path, '://'))
            ->map(fn (string $path): string => ltrim($path, '/'))
            ->reject(fn (string $path): bool => in_array('..', explode('/', $path), true))
            ->values()
            ->all();
    }

    private function skipMedia(): int
    {
        $this->warn('MEDIA_DISK steht auf "public" — Medienverwaltung wird nicht verschoben.');

        return 0;
    }

    private function moveMedia(string $targetDisk): int
    {
        if (! Schema::hasTable('media')) {
            return 0;
        }

        $model = config('media-library.media_model', Media::class);
        $updated = 0;

        $model::query()
            ->where(fn ($query) => $query->where('disk', self::SOURCE_DISK)->orWhere('conversions_disk', self::SOURCE_DISK))
            ->lazyById()
            ->each(function (Media $media) use ($targetDisk, &$updated): void {
                $before = $this->stats['conflict'] + $this->stats['failed'];

                foreach ($this->mediaPaths($media) as $path) {
                    $this->transfer($path, $targetDisk);
                }

                if ($before < $this->stats['conflict'] + $this->stats['failed']) {
                    return;
                }

                $updated++;

                if ($this->dryRun) {
                    return;
                }

                DB::table($media->getTable())->where($media->getKeyName(), $media->getKey())->update([
                    'disk' => $media->disk === self::SOURCE_DISK ? $targetDisk : $media->disk,
                    'conversions_disk' => $media->conversions_disk === self::SOURCE_DISK ? $targetDisk : $media->conversions_disk,
                ]);
            });

        return $updated;
    }

    /**
     * @return array<int, string>
     */
    private function mediaPaths(Media $media): array
    {
        $generator = PathGeneratorFactory::create($media);
        $source = Storage::disk(self::SOURCE_DISK);
        $paths = [];

        foreach ([$generator->getPath($media), $generator->getPathForConversions($media), $generator->getPathForResponsiveImages($media)] as $directory) {
            array_push($paths, ...$source->allFiles(trim($directory, '/')));
        }

        return array_values(array_unique($paths));
    }

    private function transfer(string $path, string $targetDisk): void
    {
        $source = Storage::disk(self::SOURCE_DISK);
        $target = Storage::disk($targetDisk);

        if (! $source->exists($path)) {
            $this->stats[$target->exists($path) ? 'already' : 'missing']++;

            return;
        }

        if ($target->exists($path)) {
            $this->settleExisting($path, $source, $target);

            return;
        }

        if ($this->dryRun) {
            $this->stats['moved']++;

            return;
        }

        $stream = $source->readStream($path);

        if ($stream === null || ! $target->writeStream($path, $stream) || $target->size($path) !== $source->size($path)) {
            $this->stats['failed']++;
            $this->warn("Fehlgeschlagen: {$path}");

            return;
        }

        $source->delete($path);
        $this->stats['moved']++;
    }

    private function settleExisting(string $path, Filesystem $source, Filesystem $target): void
    {
        if ($source->size($path) !== $target->size($path)) {
            $this->stats['conflict']++;
            $this->warn("Konflikt (Datei existiert bereits auf der Zielplatte, Größe weicht ab): {$path}");

            return;
        }

        if (! $this->dryRun) {
            $source->delete($path);
        }

        $this->stats['already']++;
    }

    private function report(int $mediaUpdated): void
    {
        $verb = $this->dryRun ? 'würden verschoben' : 'verschoben';
        $media = $this->dryRun ? 'würden umgestellt' : 'umgestellt';

        $this->info("Dateien {$verb}: {$this->stats['moved']}");
        $this->line("Bereits auf der Zielplatte: {$this->stats['already']}");
        $this->line("Nicht gefunden (Verweis ohne Datei): {$this->stats['missing']}");
        $this->line("Medien-Einträge {$media}: {$mediaUpdated}");

        if ($this->stats['conflict'] + $this->stats['failed'] > 0) {
            $this->error("Konflikte: {$this->stats['conflict']}, fehlgeschlagen: {$this->stats['failed']}");
        }

        if ($this->dryRun) {
            $this->comment('Trockenlauf: es wurde nichts verändert.');
        }
    }
}
