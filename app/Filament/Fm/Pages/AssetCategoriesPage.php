<?php

namespace App\Filament\Fm\Pages;

use App\Models\AssetType;
use App\Models\AssetTypeClassification;
use App\Models\ClassificationNode;
use App\Services\Classification\ClassificationTemplateImporter;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class AssetCategoriesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 25;

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', AssetType::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('classifications.asset_categories.navigation');
    }

    public function getTitle(): string
    {
        return __('classifications.asset_categories.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.objects');
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
            ->query(fn (): Builder => AssetType::query())
            ->columns([
                TextColumn::make('name')->label(__('classifications.asset_categories.asset_type'))->searchable()->sortable(),
                SelectColumn::make('classification_node_id')
                    ->label(__('classifications.asset_categories.category'))
                    ->options(fn (): array => self::categoryOptions())
                    ->placeholder(__('classifications.asset_categories.unassigned'))
                    ->selectablePlaceholder()
                    ->authorize(fn (): bool => Gate::allows('create', AssetTypeClassification::class))
                    ->getStateUsing(fn (AssetType $record): ?string => $record->categoryForCurrentTenant()?->getKey())
                    ->updateStateUsing(function (AssetType $record, mixed $state): void {
                        abort_unless(Gate::allows('create', AssetTypeClassification::class), 403);

                        $tenant = Filament::getTenant();

                        if (blank($state)) {
                            AssetTypeClassification::query()
                                ->where('organization_id', $tenant->getKey())
                                ->where('asset_type_id', $record->getKey())
                                ->delete();

                            return;
                        }

                        AssetTypeClassification::query()->updateOrCreate(
                            ['organization_id' => $tenant->getKey(), 'asset_type_id' => $record->getKey()],
                            ['classification_node_id' => $state],
                        );
                    }),
            ])
            ->defaultSort('name');
    }

    /**
     * @return array<int, string>
     */
    private static function categoryOptions(): array
    {
        return ClassificationNode::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (ClassificationNode $node): array => [$node->getKey() => $node->label()])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
        ];
    }

    private function importAction(): Action
    {
        return Action::make('import')
            ->label(__('classifications.actions.import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->authorize(fn (): bool => Gate::allows('import', ClassificationNode::class))
            ->modalWidth('sm')
            ->schema([
                Select::make('template')
                    ->label(__('classifications.import.label'))
                    ->helperText(__('classifications.import.help'))
                    ->options(ClassificationTemplateImporter::templateOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data, ClassificationTemplateImporter $importer): void {
                $count = $importer->importTemplate(Filament::getTenant(), $data['template']);

                Notification::make()->title(__('classifications.actions.imported', ['count' => $count]))->success()->send();
            });
    }
}
