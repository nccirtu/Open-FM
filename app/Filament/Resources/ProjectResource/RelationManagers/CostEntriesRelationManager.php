<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\CostEntryKind;
use App\Filament\Actions\CaptureCostEntryAction;
use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Filament\CostCenterSelectField;
use App\Models\CostEntry;
use App\Services\EntityFolders;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CostEntriesRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'costEntries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.costs.entries');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('budget_position_id')
                ->label(__('projects.costs.position'))
                ->options(fn (): array => $this->getOwnerRecord()->budgetPositions()->pluck('name', 'id')->all())
                ->native(false),
            Select::make('kind')->label(__('projects.costs.kind'))->options(CostEntryKind::class)->default(CostEntryKind::Actual)->required()->native(false),
            CostCenterSelectField::make(),
            TextInput::make('amount')->label(__('projects.costs.amount'))->numeric()->required()->suffix('€'),
            DatePicker::make('booked_on')->label(__('projects.costs.booked_on'))->default(now())->required()->native(false),
            TextInput::make('vendor')->label(__('projects.costs.vendor'))->maxLength(255),
            TextInput::make('description')->label(__('projects.costs.description'))->maxLength(255),
            FileUpload::make('receipt')->label(__('projects.costs.receipt'))->disk(Uploads::receiptDisk())->visibility('private')->directory('project-receipts')->acceptedFileTypes(Uploads::receipts())->preventFilePathTampering()->openable()->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('booked_on', 'desc')
            ->columns([
                TextColumn::make('booked_on')->label(__('projects.costs.booked_on'))->date('d.m.Y')->sortable(),
                TextColumn::make('kind')->label(__('projects.costs.kind'))->badge(),
                TextColumn::make('budgetPosition.name')->label(__('projects.costs.position'))->placeholder(__('projects.costs.without_position')),
                TextColumn::make('company.name')->label(__('projects.costs.vendor'))->state(fn (CostEntry $record): ?string => $record->company?->name ?? $record->vendor)->placeholder('—')->searchable(['vendor']),
                TextColumn::make('costCenter.name')->label(__('projects.costs.cost_center'))->state(fn (CostEntry $record): ?string => $record->costCenter?->label())->placeholder('—')->toggleable(),
                TextColumn::make('invoice_number')->label(__('receipt-capture::receipt-capture.fields.invoice_number'))->placeholder('—')->toggleable(),
                TextColumn::make('description')->label(__('projects.costs.description'))->placeholder('—')->limit(40),
                TextColumn::make('amount')->label(__('projects.costs.amount'))->money('EUR', locale: 'de')->sortable()->summarize(Sum::make()->money('EUR', locale: 'de')),
                IconColumn::make('receipt')->label(__('projects.costs.receipt'))->boolean()->getStateUsing(fn ($record): bool => filled($record->receipt)),
            ])
            ->filters([
                SelectFilter::make('kind')->label(__('projects.costs.kind'))->options(CostEntryKind::class),
                SelectFilter::make('budget_position_id')->label(__('projects.costs.position'))->options(fn (): array => $this->getOwnerRecord()->budgetPositions()->pluck('name', 'id')->all()),
            ])
            ->headerActions([
                $this->receiptCaptureAction(),
                CreateAction::make()->after(fn (CostEntry $record) => EntityFolders::importReceipt($record)),
            ])
            ->recordActions([
                ActionGroup::make([
                    CaptureCostEntryAction::preview(),
                    EditAction::make()->after(fn (CostEntry $record) => EntityFolders::importReceipt($record)),
                    DeleteAction::make(),
                ]),
            ]);
    }

    protected function receiptCaptureAction(): Action
    {
        return CaptureCostEntryAction::for($this->getOwnerRecord());
    }
}
