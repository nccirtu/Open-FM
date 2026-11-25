<?php

namespace App\Filament\RelationManagers;

use App\Enums\CostEntryKind;
use App\Filament\Actions\CaptureCostEntryAction;
use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Filament\Contracts\Resources\ContractResource;
use App\Filament\CostCenterSelectField;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Fm\Resources\TicketResource;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\PropertyResource;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\CostEntry;
use App\Models\MaintenanceOrder;
use App\Models\Property;
use App\Models\Ticket;
use App\Services\EntityFolders;
use App\Support\Uploads;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CostEntriesRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'costEntries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.costs.section');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->costEntries()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')
                ->label(__('projects.costs.vendor'))
                ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->native(false),
            Select::make('kind')->label(__('projects.costs.kind'))->options(CostEntryKind::class)->default(CostEntryKind::Actual)->required()->native(false),
            CostCenterSelectField::make(),
            TextInput::make('amount')->label(__('projects.costs.amount'))->numeric()->required()->suffix('€'),
            DatePicker::make('booked_on')->label(__('projects.costs.booked_on'))->default(now())->required()->native(false),
            TextInput::make('invoice_number')->label(__('receipt-capture::receipt-capture.fields.invoice_number'))->maxLength(100),
            TextInput::make('description')->label(__('projects.costs.description'))->maxLength(255),
            FileUpload::make('receipt')
                ->label(__('projects.costs.receipt'))
                ->disk(Uploads::receiptDisk())->visibility('private')
                ->directory(config('receipt-capture.directory', 'receipts'))
                ->acceptedFileTypes(Uploads::receipts())
                ->preventFilePathTampering()
                ->openable()
                ->columnSpanFull(),
        ])->columns(2);
    }

    /**
     * @return Builder<CostEntry>|null
     */
    protected function costEntriesQuery(): ?Builder
    {
        return null;
    }

    protected function showsSource(): bool
    {
        return false;
    }

    public static function sourceLabel(CostEntry $entry): ?string
    {
        if ($entry->project !== null) {
            return __('projects.model').': '.$entry->project->name;
        }

        $costable = $entry->costable;

        return match (true) {
            $costable instanceof Asset => AssetResource::getModelLabel().': '.$costable->name,
            $costable instanceof Ticket => TicketResource::getModelLabel().': #'.$costable->getKey().' '.$costable->title,
            $costable instanceof MaintenanceOrder => MaintenanceOrderResource::getModelLabel().': '.($costable->plan?->name ?? '').' ('.$costable->due_at?->format('d.m.Y').')',
            $costable instanceof Property => PropertyResource::getModelLabel().': '.$costable->property_number,
            $costable instanceof Contract => ContractResource::getModelLabel().': '.$costable->name,
            default => null,
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->when($this->costEntriesQuery(), fn (Table $table, Builder $query): Table => $table->query($query->with(['project', 'costable'])))
            ->defaultSort('booked_on', 'desc')
            ->columns([
                TextColumn::make('source')
                    ->label(__('projects.costs.source'))
                    ->state(fn (CostEntry $record): ?string => self::sourceLabel($record))
                    ->placeholder('—')
                    ->visible($this->showsSource()),
                TextColumn::make('booked_on')->label(__('projects.costs.booked_on'))->date('d.m.Y')->sortable(),
                TextColumn::make('kind')->label(__('projects.costs.kind'))->badge(),
                TextColumn::make('company.name')->label(__('projects.costs.vendor'))->state(fn (CostEntry $record): ?string => $record->company?->name ?? $record->vendor)->placeholder('—'),
                TextColumn::make('costCenter.name')->label(__('projects.costs.cost_center'))->state(fn (CostEntry $record): ?string => $record->costCenter?->label())->placeholder('—')->toggleable(),
                TextColumn::make('invoice_number')->label(__('receipt-capture::receipt-capture.fields.invoice_number'))->placeholder('—')->toggleable(),
                TextColumn::make('description')->label(__('projects.costs.description'))->placeholder('—')->limit(40),
                TextColumn::make('amount')->label(__('projects.costs.amount'))->money('EUR', locale: 'de')->sortable()->summarize(Sum::make()->money('EUR', locale: 'de')),
                IconColumn::make('receipt')->label(__('projects.costs.receipt'))->boolean()->getStateUsing(fn (CostEntry $record): bool => filled($record->receipt)),
            ])
            ->filters([
                SelectFilter::make('kind')->label(__('projects.costs.kind'))->options(CostEntryKind::class),
            ])
            ->headerActions($this->isReadOnly() ? [] : [
                CaptureCostEntryAction::for($this->getOwnerRecord()),
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
}
