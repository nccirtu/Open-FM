<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Actions\CaptureCostEntryAction;
use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Filament\CostCenterField;
use App\Models\BudgetPosition;
use App\Models\CostEntry;
use App\Support\Uploads;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BudgetPositionsRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'budgetPositions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.costs.positions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('projects.costs.position_name'))->required()->maxLength(255),
            CostCenterField::make(),
            TextInput::make('budget')->label(__('projects.costs.budget'))->numeric()->minValue(0)->required()->suffix('€'),
            TextInput::make('sort')->label(__('projects.phases.sort'))->numeric()->default(fn (): int => ($this->getOwnerRecord()->budgetPositions()->max('sort') ?? 0) + 1),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('cost_group')->label(__('projects.costs.cost_group'))->state(fn (BudgetPosition $record): ?string => $record->costGroupLabel())->placeholder('—'),
                TextColumn::make('name')->label(__('projects.costs.position_name'))->weight('medium'),
                TextColumn::make('budget')->label(__('projects.costs.budget'))->money('EUR', locale: 'de')->summarize(Sum::make()->money('EUR', locale: 'de')),
                TextColumn::make('approved_changes')->label(__('projects.costs.approved_changes'))->state(fn (BudgetPosition $record): float => $record->approvedChanges())->money('EUR', locale: 'de'),
                TextColumn::make('actual')->label(__('projects.costs.actual'))->state(fn (BudgetPosition $record): float => $record->actual())->money('EUR', locale: 'de'),
                TextColumn::make('variance')
                    ->label(__('projects.costs.variance'))
                    ->state(fn (BudgetPosition $record): float => $record->variance())
                    ->money('EUR', locale: 'de')
                    ->color(fn (BudgetPosition $record): string => $record->variance() < 0 ? 'danger' : 'success')
                    ->weight('medium'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                ActionGroup::make([
                    $this->detailsAction(),
                    CaptureCostEntryAction::for($this->getOwnerRecord(), fixedCategory: true),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->recordAction('details');
    }

    protected function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('projects.costs.details'))
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::FourExtraLarge)
            ->modalHeading(fn (BudgetPosition $record): string => $record->name)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('receipt-capture::receipt-capture.preview.close'))
            ->schema(fn (BudgetPosition $record): array => [
                Grid::make(4)->schema([
                    TextEntry::make('budget')->label(__('projects.costs.budget'))->state($record->budget)->money('EUR', locale: 'de'),
                    TextEntry::make('approved')->label(__('projects.costs.approved_changes'))->state($record->approvedChanges())->money('EUR', locale: 'de'),
                    TextEntry::make('actual')->label(__('projects.costs.actual'))->state($record->actual())->money('EUR', locale: 'de'),
                    TextEntry::make('variance')->label(__('projects.costs.variance'))->state($record->variance())->money('EUR', locale: 'de')->color($record->variance() < 0 ? 'danger' : 'success')->weight('bold'),
                ]),
                RepeatableEntry::make('entries')
                    ->label(__('projects.costs.entries'))
                    ->state($record->costEntries()->with('company')->orderByDesc('booked_on')->get()->map(fn (CostEntry $entry): array => [
                        'booked_on' => $entry->booked_on?->format('d.m.Y'),
                        'kind' => $entry->kind?->getLabel(),
                        'company' => $entry->company?->name ?? $entry->vendor,
                        'invoice_number' => $entry->invoice_number,
                        'amount' => (float) $entry->amount,
                        'receipt' => $entry->receipt ? Uploads::url($entry->receipt, Uploads::receiptDisk()) : null,
                    ])->all())
                    ->schema([
                        TextEntry::make('booked_on')->label(__('projects.costs.booked_on')),
                        TextEntry::make('kind')->label(__('projects.costs.kind'))->badge(),
                        TextEntry::make('company')->label(__('projects.costs.vendor'))->placeholder('—'),
                        TextEntry::make('invoice_number')->label(__('receipt-capture::receipt-capture.fields.invoice_number'))->placeholder('—'),
                        TextEntry::make('amount')->label(__('projects.costs.amount'))->money('EUR', locale: 'de'),
                        TextEntry::make('receipt')
                            ->label(__('projects.costs.receipt'))
                            ->formatStateUsing(fn (?string $state): ?string => filled($state) ? __('receipt-capture::receipt-capture.preview.label') : null)
                            ->url(fn (?string $state): ?string => $state, shouldOpenInNewTab: true)
                            ->icon('heroicon-o-document-magnifying-glass')
                            ->placeholder('—'),
                    ])
                    ->columns(6)
                    ->placeholder('—'),
            ])
            ->extraModalFooterActions([
                CaptureCostEntryAction::for($this->getOwnerRecord(), fixedCategory: true)
                    ->cancelParentActions(),
            ]);
    }
}
