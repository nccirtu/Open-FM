<?php

namespace App\Filament\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Services\EntityFolders;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Slimani\MediaManager\Models\File;
use Slimani\MediaManager\Models\Folder;
use Slimani\MediaManager\Pages\MediaManager;
use Slimani\MediaManager\Services\MediaUploadService;
use Slimani\MediaManager\Tables\Columns\MediaColumn;

class FolderFilesRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'mediaFiles';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-folder';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('documents.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $folder = EntityFolders::folderFor($ownerRecord, create: false);
        $count = $folder === null ? 0 : $folder->getRecursiveStats()['files_count'];

        return $count > 0 ? (string) $count : null;
    }

    protected function folder(): ?Folder
    {
        return EntityFolders::folderFor($this->getOwnerRecord());
    }

    /**
     * @return Builder<File>
     */
    protected function filesQuery(): Builder
    {
        $folder = $this->folder();

        if ($folder === null) {
            return File::query()->whereRaw('1 = 0');
        }

        return File::query()
            ->whereIn('folder_id', [$folder->getKey(), ...$folder->getAllDescendantIds()])
            ->with(['folder', 'uploader']);
    }

    public function table(Table $table): Table
    {
        $folder = $this->folder();
        $basePath = $folder === null ? '' : EntityFolders::pathOf($folder);

        return $table
            ->query(fn (): Builder => $this->filesQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('documents.media.empty'))
            ->columns([
                MediaColumn::make('preview')->label('')->state(fn (File $record): File => $record)->conversion('thumb')->square()->size(40),
                TextColumn::make('name')->label(__('documents.fields.title'))->searchable()->weight('medium')->url(fn (File $record): ?string => $record->getUrl(), shouldOpenInNewTab: true),
                TextColumn::make('subfolder')
                    ->label(__('documents.media.folder'))
                    ->state(fn (File $record): ?string => $record->folder ? trim(mb_substr(EntityFolders::pathOf($record->folder), mb_strlen($basePath)), '/') : null)
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('mime_type')->label(__('documents.media.type'))->badge()->formatStateUsing(fn (?string $state): string => mb_strtoupper((string) (str_contains((string) $state, '/') ? substr((string) $state, strrpos((string) $state, '/') + 1) : $state)))->placeholder('—'),
                TextColumn::make('size')->label(__('documents.media.size'))->formatStateUsing(fn (?int $state): string => Number::fileSize((int) $state))->sortable(),
                TextColumn::make('uploader.name')->label(__('documents.media.uploaded_by'))->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label(__('documents.fields.created_at'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label(__('documents.media.upload'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->authorize('create')
                    ->visible(fn (): bool => $this->folder() !== null && ! $this->isReadOnly())
                    ->schema([
                        FileUpload::make('files')
                            ->label(__('documents.fields.files'))
                            ->multiple()
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes(Uploads::documents())
                            ->maxSize(10240),
                    ])
                    ->action(function (array $data): void {
                        $folder = $this->folder();
                        $service = app(MediaUploadService::class);

                        foreach ($data['files'] ?? [] as $upload) {
                            if ($upload instanceof TemporaryUploadedFile) {
                                $file = $service->upload($upload, $folder);
                                $media = $file->getFirstMedia('default');
                                $file->update(['uploaded_by_user_id' => auth()->id(), 'size' => $media?->size, 'mime_type' => $media?->mime_type, 'extension' => $media?->extension]);
                            }
                        }

                        Notification::make()->title(__('documents.media.uploaded'))->success()->send();
                    }),
                Action::make('openManager')
                    ->label(__('documents.media.open_manager'))
                    ->icon('heroicon-o-folder-open')
                    ->color('gray')
                    ->visible(fn (): bool => $this->folder() !== null)
                    ->url(fn (): string => MediaManager::getUrl(['folder' => $this->folder()?->getKey()])),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('open')
                        ->label(__('filament-actions::view.single.label'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (File $record): ?string => $record->getUrl(), shouldOpenInNewTab: true),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
