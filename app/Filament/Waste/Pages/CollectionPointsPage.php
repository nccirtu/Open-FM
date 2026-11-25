<?php

namespace App\Filament\Waste\Pages;

use App\Enums\StructureNodeType;
use App\Filament\Waste\Resources\WasteContainers\WasteContainerResource;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteContainer;
use App\Services\Waste\CollectionPoints;
use App\Support\Plugins\QrCodeBridge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CollectionPointsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 22;

    protected static ?string $slug = 'sammelstellen';

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.collection_points');
    }

    public function getTitle(): string
    {
        return __('waste.pages.collection_points');
    }

    public function getSubheading(): ?string
    {
        return __('waste.collection_points.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WasteContainer::class) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(CollectionPoints::class)->query()
                ->with(['property', 'parent', 'wasteContainers.fraction'])
                ->withCount('wasteContainers'))
            ->defaultSort('name')
            ->emptyStateHeading(__('waste.collection_points.empty'))
            ->emptyStateDescription(__('waste.collection_points.empty_help'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('waste.fields.collection_point'))
                    ->description(fn (StructureNode $record): string => $record->fullPath())
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.property_number')
                    ->label(__('property.model'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('waste.collection_points.type'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('waste_containers_count')
                    ->label(__('waste.models.container.plural'))
                    ->numeric(),
                TextColumn::make('fractions')
                    ->label(__('waste.models.fraction.plural'))
                    ->state(fn (StructureNode $record): array => $record->wasteContainers->pluck('fraction.name')->filter()->unique()->values()->all())
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('property.model'))
                    ->relationship('property', 'property_number')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                $this->createAction(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('addContainer')
                        ->label(__('waste.collection_points.add_container'))
                        ->icon(Heroicon::OutlinedPlus)
                        ->visible(fn (): bool => WasteContainerResource::canCreate())
                        ->url(fn (StructureNode $record): string => WasteContainerResource::getUrl('create', ['property' => $record->property_id, 'node' => $record->getKey()])),
                    Action::make('confirmPickups')
                        ->label(__('waste.pages.confirm_pickups'))
                        ->icon(Heroicon::OutlinedDevicePhoneMobile)
                        ->visible(fn (StructureNode $record): bool => $record->waste_containers_count > 0 && ConfirmPickupsPage::canAccess())
                        ->url(fn (StructureNode $record): string => ConfirmPickupsPage::getUrl(['property' => $record->property_id, 'node' => $record->getKey()])),
                    QrCodeBridge::action(),
                    $this->editAction(),
                    $this->deleteAction(),
                ]),
            ]);
    }

    private function createAction(): Action
    {
        return Action::make('createCollectionPoint')
            ->label(__('waste.collection_points.create'))
            ->icon(Heroicon::OutlinedPlus)
            ->authorize(fn (): bool => auth()->user()?->can('create', WasteContainer::class) ?? false)
            ->schema(self::formSchema(withProperty: true))
            ->action(function (array $data): void {
                app(CollectionPoints::class)->create(
                    Property::query()->findOrFail($data['property_id']),
                    $data['name'],
                    StructureNodeType::from($data['type']),
                    $data['parent_id'] ?? null,
                );

                Notification::make()->success()->title(__('waste.collection_points.created'))->send();
            });
    }

    private function editAction(): Action
    {
        return Action::make('editCollectionPoint')
            ->label(__('filament-actions::edit.single.label'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->authorize(fn (): bool => auth()->user()?->can('create', WasteContainer::class) ?? false)
            ->fillForm(fn (StructureNode $record): array => [
                'property_id' => $record->property_id,
                'name' => $record->name,
                'type' => $record->type?->value,
                'parent_id' => $record->parent_id,
            ])
            ->schema(fn (StructureNode $record): array => self::formSchema(withProperty: false, propertyId: $record->property_id, except: $record->getKey()))
            ->action(function (StructureNode $record, array $data): void {
                $record->update([
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'parent_id' => filled($data['parent_id'] ?? null) ? $data['parent_id'] : null,
                ]);

                Notification::make()->success()->title(__('waste.collection_points.saved'))->send();
            });
    }

    private function deleteAction(): Action
    {
        return Action::make('deleteCollectionPoint')
            ->label(__('filament-actions::delete.single.label'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->authorize(fn (): bool => auth()->user()?->can('create', WasteContainer::class) ?? false)
            ->visible(fn (StructureNode $record): bool => $record->waste_containers_count === 0 && ! $record->children()->exists() && ! $record->assets()->exists())
            ->action(fn (StructureNode $record) => $record->delete());
    }

    /**
     * @return array<int, mixed>
     */
    public static function formSchema(bool $withProperty = false, ?string $propertyId = null, ?string $except = null): array
    {
        return [
            Select::make('property_id')
                ->label(__('property.model'))
                ->options(fn (): array => Property::query()->orderBy('property_number')->pluck('property_number', 'id')->all())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('parent_id', null))
                ->visible($withProperty),
            TextInput::make('name')
                ->label(__('waste.fields.name'))
                ->placeholder(__('waste.collection_points.name_placeholder'))
                ->required()
                ->maxLength(255),
            ToggleButtons::make('type')
                ->label(__('waste.collection_points.type'))
                ->options(CollectionPoints::typeOptions())
                ->default(StructureNodeType::Outdoor->value)
                ->inline()
                ->required(),
            Select::make('parent_id')
                ->label(__('waste.collection_points.parent'))
                ->helperText(__('waste.collection_points.parent_help'))
                ->options(fn (Get $get): array => collect(StructureNode::locationOptions($propertyId ?? $get('property_id')))->except($except)->all())
                ->searchable(),
        ];
    }
}
