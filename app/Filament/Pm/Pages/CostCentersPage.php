<?php

namespace App\Filament\Pm\Pages;

use App\Models\ClassificationNode;
use App\Models\Project;
use App\Services\Classification\ClassificationTemplateImporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CostCentersPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', ClassificationNode::class) && Gate::allows('viewAny', Project::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('classifications.cost_centers.navigation');
    }

    public function getTitle(): string
    {
        return __('classifications.cost_centers.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('projects.navigation_group');
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
            ->query(fn (): Builder => ClassificationNode::query()->with('parent'))
            ->columns([
                TextColumn::make('code')->label(__('classifications.cost_centers.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('classifications.cost_centers.name'))->searchable()->sortable(),
                TextColumn::make('parent.name')->label(__('classifications.cost_centers.parent'))->placeholder('—'),
            ])
            ->defaultSort('code')
            ->emptyStateHeading(__('classifications.cost_centers.empty'))
            ->recordActions([
                ActionGroup::make([
                    $this->editAction(),
                    DeleteAction::make()->authorize('delete'),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createAction(),
            $this->importAction(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    private static function nodeFormComponents(?ClassificationNode $record = null): array
    {
        return [
            TextInput::make('code')->label(__('classifications.fields.code'))->required()->maxLength(50),
            TextInput::make('name')->label(__('classifications.fields.name'))->required()->maxLength(255),
            Select::make('parent_id')
                ->label(__('classifications.cost_centers.parent'))
                ->options(fn (): array => ClassificationNode::query()
                    ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                    ->orderBy('code')
                    ->get()
                    ->mapWithKeys(fn (ClassificationNode $node): array => [$node->getKey() => $node->label()])
                    ->all())
                ->searchable()
                ->native(false),
        ];
    }

    private function createAction(): Action
    {
        return Action::make('create')
            ->label(__('classifications.actions.create'))
            ->icon(Heroicon::OutlinedPlus)
            ->authorize(fn (): bool => Gate::allows('create', ClassificationNode::class))
            ->modalWidth('md')
            ->schema(self::nodeFormComponents())
            ->action(fn (array $data) => ClassificationNode::query()->create($data));
    }

    private function editAction(): Action
    {
        return Action::make('edit')
            ->label(__('classifications.actions.edit'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->authorize('update')
            ->modalWidth('md')
            ->fillForm(fn (ClassificationNode $record): array => $record->only(['code', 'name', 'parent_id']))
            ->schema(fn (ClassificationNode $record): array => self::nodeFormComponents($record))
            ->action(fn (array $data, ClassificationNode $record) => $record->update($data));
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
