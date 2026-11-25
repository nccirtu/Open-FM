<?php

namespace App\Filament\RelationManagers;

use App\Filament\Contracts\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('contracts.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->contracts()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('end_date')
            ->columns([
                TextColumn::make('name')->label(__('contracts.fields.name'))->searchable()->weight('medium'),
                TextColumn::make('partner_name')->label(__('contracts.fields.partner_name'))->placeholder('—'),
                TextColumn::make('type')->label(__('contracts.fields.type'))->badge(),
                TextColumn::make('status')->label(__('contracts.fields.status'))->badge(),
                TextColumn::make('end_date')->label(__('contracts.fields.end_date'))->date('d.m.Y')->placeholder(__('contracts.fields.open_ended'))->sortable(),
                TextColumn::make('amount')->label(__('contracts.fields.amount'))->money('EUR', locale: 'de')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Contract $record): string => ContractResource::getUrl('view', ['record' => $record], panel: 'contracts')),
            ]);
    }
}
