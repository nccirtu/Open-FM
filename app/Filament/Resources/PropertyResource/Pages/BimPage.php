<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\AssetResource;
use App\Filament\Resources\PropertyResource;
use App\Models\Asset;
use App\Models\BimModel;
use App\Models\StructureNode;
use App\Services\EntityFolders;
use App\Support\Plugins\Plugins;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class BimPage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = PropertyResource::class;

    protected string $view = 'filament.pages.property-bim';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(PropertyResource::canView($this->record), 403);
    }

    public function getTitle(): string
    {
        return __('property.bim.title', ['property' => $this->record->property_number]);
    }

    public function getBimModel(): ?BimModel
    {
        return BimModel::query()
            ->where('property_id', $this->record->getKey())
            ->latest()
            ->first();
    }

    public ?string $inspectedGuid = null;

    public ?string $inspectedName = null;

    public function inspectElement(string $guid, string $name): void
    {
        $this->inspectedGuid = $guid;
        $this->inspectedName = $name;

        $this->mountAction('inspectElement');
    }

    public function inspectElementAction(): Action
    {
        return Action::make('inspectElement')
            ->slideOver()
            ->modalWidth(Width::Medium)
            ->modalHeading(fn (): string => $this->inspectedName ?? __('property.bim.inspector.title'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('app.close'))
            ->schema(function (): array {
                $guid = $this->inspectedGuid;

                $asset = $this->findByGuid(Asset::class, $guid);
                $node = $this->findByGuid(StructureNode::class, $guid);

                $entries = [
                    TextEntry::make('guid')
                        ->label(__('property.bim.ifc_guid'))
                        ->state($guid ?? '—'),
                ];

                if ($asset !== null) {
                    $entries[] = TextEntry::make('linked_asset')
                        ->label(__('property.bim.inspector.linked_asset'))
                        ->state(trim($asset->name.' — '.($asset->assetType?->name ?? ''), ' —'))
                        ->badge()
                        ->color('success');

                    if ($asset->structureNode !== null) {
                        $entries[] = TextEntry::make('asset_location')
                            ->label(__('assets.sections.location'))
                            ->state($asset->structureNode->fullPath());
                    }
                } elseif ($node !== null) {
                    $entries[] = TextEntry::make('linked_node')
                        ->label(__('property.bim.inspector.linked_node'))
                        ->state($node->type->getLabel().': '.$node->fullPath())
                        ->badge()
                        ->color('info');

                    if ($node->area !== null) {
                        $entries[] = TextEntry::make('node_area')
                            ->label(__('property.structure.area'))
                            ->state(number_format((float) $node->area, 2, ',', '.'));
                    }
                } else {
                    $entries[] = TextEntry::make('no_link')
                        ->hiddenLabel()
                        ->state(__('property.bim.inspector.no_link'))
                        ->color('gray');
                }

                return $entries;
            })
            ->extraModalFooterActions(function (): array {
                $guid = $this->inspectedGuid;

                $asset = $this->findByGuid(Asset::class, $guid);
                $node = $this->findByGuid(StructureNode::class, $guid);

                $actions = [];

                if ($asset !== null) {
                    $actions[] = Action::make('openAsset')
                        ->label(__('property.bim.inspector.open_asset'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(AssetResource::getUrl('edit', ['record' => $asset]));
                } elseif ($node !== null) {
                    $actions[] = Action::make('openStructure')
                        ->label(__('property.bim.inspector.open_structure'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(PropertyResource::getUrl('edit', ['record' => $this->record]));
                } else {
                    $actions[] = $this->createAssetFromBimAction();
                }

                return $actions;
            });
    }

    protected function createAssetFromBimAction(): CreateAction
    {
        return CreateAction::make('createAssetFromBim')
            ->model(Asset::class)
            ->label(__('property.bim.inspector.create_asset'))
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->modalHeading(__('property.bim.inspector.create_asset'))
            ->modalWidth(Width::FourExtraLarge)
            ->visible(fn (): bool => AssetResource::canCreate()
                && $this->inspectedGuid !== null
                && $this->findByGuid(Asset::class, $this->inspectedGuid) === null
                && $this->findByGuid(StructureNode::class, $this->inspectedGuid) === null)
            ->createAnother(false)
            ->schema(function (Schema $schema): Schema {
                AssetResource::form($schema)
                    ->getComponentByStatePath('property_id', withHidden: true)
                    ?->disabled();

                return $schema;
            })
            ->mountUsing(function (Schema $schema): void {
                $schema->fill();
                $schema->fillPartially([
                    'property_id' => $this->record->getKey(),
                    'name' => $this->inspectedName,
                ], ['property_id', 'name']);
            })
            ->mutateDataUsing(fn (array $data): array => [
                ...$data,
                'property_id' => $this->record->getKey(),
                'ifc_guid' => $this->inspectedGuid,
            ])
            ->after(fn (Asset $record) => EntityFolders::adoptRootFiles($record))
            ->successNotificationTitle(__('property.bim.inspector.asset_created'));
    }

    /**
     * @param  class-string<Asset|StructureNode>  $model
     */
    public function findByGuid(string $model, ?string $guid): Asset|StructureNode|null
    {
        abort_unless(in_array($model, [Asset::class, StructureNode::class], true), 403);

        if ($guid === null) {
            return null;
        }

        return $model::query()
            ->where('property_id', $this->record->getKey())
            ->where('ifc_guid', $guid)
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('property.bim.upload'))
                ->icon('heroicon-o-arrow-up-tray')
                ->authorize(fn (): bool => PropertyResource::canEdit($this->record))
                ->visible(fn (): bool => Plugins::active('bim-viewer'))
                ->modalHeading(__('property.bim.upload'))
                ->schema([
                    FileUpload::make('file')
                        ->label(__('property.bim.file'))
                        ->helperText(__('property.bim.file_help'))
                        ->disk('local')
                        ->directory('bim-models')
                        ->acceptedFileTypes(Uploads::ifc())
                        ->mimeTypeMap(['ifc' => 'application/octet-stream'])
                        ->rule('extensions:ifc')
                        ->validationMessages(['extensions' => __('property.bim.file_extension')])
                        ->preventFilePathTampering()
                        ->storeFileNamesIn('original_name')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = (string) ($data['file'] ?? '');

                    if (! str_starts_with($path, 'bim-models/') || in_array('..', explode('/', $path), true)) {
                        Notification::make()
                            ->title(__('property.bim.upload_rejected'))
                            ->danger()
                            ->send();

                        return;
                    }

                    BimModel::create([
                        'property_id' => $this->record->getKey(),
                        'name' => $data['original_name'] ?? basename($data['file']),
                        'file_path' => $data['file'],
                    ]);

                    Notification::make()
                        ->title(__('property.bim.uploaded'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
