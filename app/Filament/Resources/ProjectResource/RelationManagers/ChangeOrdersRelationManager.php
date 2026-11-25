<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ChangeOrderStatus;
use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Models\ChangeOrder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChangeOrdersRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'changeOrders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.costs.change_orders');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label(__('projects.costs.title'))->required()->maxLength(255)->columnSpanFull(),
            Select::make('budget_position_id')
                ->label(__('projects.costs.position'))
                ->options(fn (): array => $this->getOwnerRecord()->budgetPositions()->pluck('name', 'id')->all())
                ->native(false),
            TextInput::make('amount')->label(__('projects.costs.amount'))->numeric()->required()->suffix('€'),
            DatePicker::make('requested_on')->label(__('projects.costs.requested_on'))->default(now())->native(false),
            Textarea::make('reason')->label(__('projects.costs.reason'))->rows(3)->columnSpanFull(),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_on', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('projects.costs.title'))->weight('medium')->searchable(),
                TextColumn::make('budgetPosition.name')->label(__('projects.costs.position'))->placeholder(__('projects.costs.without_position')),
                TextColumn::make('amount')->label(__('projects.costs.amount'))->money('EUR', locale: 'de'),
                TextColumn::make('status')->label(__('projects.costs.status'))->badge(),
                TextColumn::make('requested_on')->label(__('projects.costs.requested_on'))->date('d.m.Y')->placeholder('—'),
                TextColumn::make('decider.name')->label(__('projects.costs.decided_by'))->placeholder('—')->description(fn (ChangeOrder $record): ?string => $record->decided_at?->format('d.m.Y')),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->authorize('decide')
                        ->label(__('projects.costs.approve'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (ChangeOrder $record): bool => $record->status === ChangeOrderStatus::Pending)
                        ->action(fn (ChangeOrder $record) => $record->decide(ChangeOrderStatus::Approved, Filament::auth()->id())),
                    Action::make('reject')
                        ->authorize('decide')
                        ->label(__('projects.costs.reject'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (ChangeOrder $record): bool => $record->status === ChangeOrderStatus::Pending)
                        ->action(fn (ChangeOrder $record) => $record->decide(ChangeOrderStatus::Rejected, Filament::auth()->id())),
                    Action::make('reopen')
                        ->authorize('decide')
                        ->label(__('projects.costs.reopen'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn (ChangeOrder $record): bool => $record->status !== ChangeOrderStatus::Pending)
                        ->action(fn (ChangeOrder $record) => $record->forceFill(['status' => ChangeOrderStatus::Pending, 'decided_at' => null, 'decided_by' => null])->save()),
                    EditAction::make()->visible(fn (ChangeOrder $record): bool => $record->status === ChangeOrderStatus::Pending),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
