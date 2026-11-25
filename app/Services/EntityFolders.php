<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CostEntry;
use App\Models\EntityMediaFolder;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Support\Uploads;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Models\MediaAttachment;
use Slimani\MediaManager\Services\MediaUploadService;
use Throwable;

class EntityFolders
{
    public const ASSETS = 'Assets';

    public const STRUCTURE = 'Struktur';

    public const PROJECTS = 'Projekte';

    public const TICKETS = 'Störungen';

    public const MAINTENANCE = 'Wartung';

    public const RECEIPTS = 'Belege';

    public const CONTRACTS = 'Verträge';

    public const COMPANIES = 'Firmen';

    /** @var array<class-string<Model>> */
    public const MODELS = [
        Property::class, StructureNode::class, Asset::class, Project::class, Ticket::class,
        MaintenancePlan::class, MaintenanceOrder::class, Contract::class, Company::class,
    ];

    public static function registerModelHooks(): void
    {
        foreach (self::MODELS as $model) {
            $model::created(fn (Model $entity) => self::quietly(fn () => self::folderFor($entity)));
            $model::updated(fn (Model $entity) => self::quietly(fn () => self::sync($entity)));
        }

        MediaAttachment::created(function (MediaAttachment $attachment): void {
            self::quietly(function () use ($attachment): void {
                $entity = $attachment->attachable;
                $file = $attachment->file;

                if ($entity instanceof Model && $file instanceof File && $file->folder_id === null && self::supports($entity)) {
                    $folder = self::folderFor($entity);

                    if ($folder !== null) {
                        $file->update(['folder_id' => $folder->getKey()]);
                    }
                }
            });
        });
    }

    public static function supports(Model $entity): bool
    {
        return in_array($entity::class, self::MODELS, true);
    }

    public static function isAvailable(): bool
    {
        try {
            $plugin = Filament::getCurrentPanel()?->getPlugin('media-manager');

            return $plugin !== null && (! $plugin->isTenantAware() || $plugin->getTenantKey() !== null);
        } catch (Throwable) {
            return false;
        }
    }

    public static function folderFor(Model $entity, bool $create = true): ?Folder
    {
        if (! self::supports($entity) || ! self::isAvailable()) {
            return null;
        }

        $link = EntityMediaFolder::query()
            ->where('folderable_type', $entity->getMorphClass())
            ->where('folderable_id', $entity->getKey())
            ->first();

        if ($link !== null) {
            $folder = Folder::query()->find($link->folder_id);

            if ($folder !== null) {
                return $folder;
            }

            $link->delete();
        }

        if (! $create) {
            return null;
        }

        $parent = self::parentFolderFor($entity);
        $folder = self::child($parent, self::nameFor($entity));

        EntityMediaFolder::query()->create([
            'folder_id' => $folder->getKey(),
            'folderable_type' => $entity->getMorphClass(),
            'folderable_id' => $entity->getKey(),
        ]);

        return $folder;
    }

    public static function receiptsFolderFor(Model $owner): ?Folder
    {
        $folder = self::folderFor($owner);

        return $folder === null ? null : self::child($folder, self::RECEIPTS);
    }

    public static function pathFor(?Model $entity): ?string
    {
        if ($entity === null || ! $entity->exists) {
            return null;
        }

        $folder = self::folderFor($entity);

        return $folder === null ? null : self::pathOf($folder);
    }

    public static function pathOf(Folder $folder): string
    {
        $segments = [];

        for ($current = $folder; $current !== null; $current = $current->parent_id ? Folder::query()->find($current->parent_id) : null) {
            array_unshift($segments, $current->name);
        }

        return implode('/', $segments);
    }

    public static function sync(Model $entity): void
    {
        $folder = self::folderFor($entity, create: false);

        if ($folder === null) {
            return;
        }

        $name = self::nameFor($entity);
        $parent = self::parentFolderFor($entity);
        $parentId = $parent?->getKey();

        if ($folder->name !== $name || $folder->parent_id !== $parentId) {
            $folder->update(['name' => $name, 'parent_id' => $parentId]);
        }
    }

    /**
     * @return array{file: File|null, path: string|null}
     */
    public static function storeReceipt(Model $owner, UploadedFile $upload): array
    {
        $folder = self::receiptsFolderFor($owner);

        if ($folder === null) {
            return ['file' => null, 'path' => null];
        }

        $file = app(MediaUploadService::class)->upload($upload, $folder);
        $media = $file->getFirstMedia('default');

        $file->update([
            'uploaded_by_user_id' => Filament::auth()->id(),
            'size' => $media?->size,
            'mime_type' => $media?->mime_type,
            'extension' => $media?->extension,
        ]);

        return ['file' => $file, 'path' => $media?->getPathRelativeToRoot()];
    }

    public static function importReceipt(CostEntry $entry): void
    {
        $owner = $entry->costable ?? $entry->project;
        $path = $entry->receipt;

        if ($owner === null || blank($path) || $entry->mediaFiles('receipt')->exists()) {
            return;
        }

        $disk = Storage::disk(Uploads::receiptDisk());

        if (! $disk->exists($path)) {
            return;
        }

        $upload = new UploadedFile($disk->path($path), basename($path), $disk->mimeType($path) ?: null, null, true);
        ['file' => $file, 'path' => $mediaPath] = self::storeReceipt($owner, $upload);

        if ($file === null) {
            return;
        }

        self::attachReceipt($entry, $file);
        $entry->forceFill(['receipt' => $mediaPath])->saveQuietly();
        $disk->delete($path);
    }

    public static function adoptRootFiles(Model $entity): void
    {
        if (! self::supports($entity) || ! method_exists($entity, 'mediaFiles')) {
            return;
        }

        $files = $entity->mediaFiles()->whereNull('folder_id')->get();

        if ($files->isEmpty()) {
            return;
        }

        $folder = self::folderFor($entity);

        if ($folder !== null) {
            $files->each(fn (File $file) => $file->update(['folder_id' => $folder->getKey()]));
        }
    }

    public static function attachReceipt(CostEntry $entry, File $file): void
    {
        $entry->mediaFiles()->syncWithoutDetaching([$file->getKey() => ['collection' => 'receipt']]);
    }

    public static function nameFor(Model $entity): string
    {
        $name = match (true) {
            $entity instanceof Property => $entity->property_number,
            $entity instanceof Ticket => '#'.($entity->ticket_number ?? $entity->getKey()).' '.$entity->title,
            $entity instanceof MaintenanceOrder => $entity->due_at?->format('Y-m-d') ?? '#'.$entity->getKey(),
            default => $entity->getAttribute('name'),
        };

        $name = trim(str_replace(['/', '\\'], '-', (string) $name));

        return $name !== '' ? $name : '#'.$entity->getKey();
    }

    protected static function parentFolderFor(Model $entity): ?Folder
    {
        [$parentEntity, $group] = match (true) {
            $entity instanceof Property => [null, null],
            $entity instanceof StructureNode => $entity->parent_id !== null
                ? [$entity->parent()->first(), null]
                : [$entity->property()->first(), self::STRUCTURE],
            $entity instanceof Asset => [$entity->property()->first(), self::ASSETS],
            $entity instanceof Project => [$entity->property()->first(), self::PROJECTS],
            $entity instanceof Ticket => [$entity->property()->first(), self::TICKETS],
            $entity instanceof MaintenancePlan => [$entity->asset()->first(), self::MAINTENANCE],
            $entity instanceof MaintenanceOrder => [$entity->plan()->first(), null],
            $entity instanceof Contract => [null, self::CONTRACTS],
            $entity instanceof Company => [null, self::COMPANIES],
            default => [null, null],
        };

        $parent = $parentEntity instanceof Model ? self::folderFor($parentEntity) : null;

        return $group === null ? $parent : self::child($parent, $group);
    }

    protected static function child(?Folder $parent, string $name): Folder
    {
        return Folder::query()->firstOrCreate(['name' => $name, 'parent_id' => $parent?->getKey()]);
    }

    protected static function quietly(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
