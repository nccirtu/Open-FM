<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Filament\Resources\AssetResource;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'assets';

    protected static ?string $title = 'Assets';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-cube';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label(__('assets.fields.name'))->searchable()->sortable(),
                TextColumn::make('assetType.name')->label(__('assets.fields.type'))->formatStateUsing(fn (Asset $record): ?string => $record->assetType?->displayLabelForCurrentTenant()),
                TextColumn::make('manufacturer')->label(__('assets.fields.manufacturer')),
                TextColumn::make('company.name')->label(__('assets.fields.maintenance_partner'))->toggleable(),
                TextColumn::make('inspectionCompany.name')->label(__('assets.fields.inspection_company'))->toggleable(),
                TextColumn::make('serial_number')->label(__('assets.fields.serial_number_short'))->toggleable(),
                TextColumn::make('next_maintenance_at')->label(__('assets.fields.next_maintenance'))->date('d.m.Y')->sortable(),
            ])
            ->defaultSort('name')
            ->headerActions([
                Action::make('create')
                    ->label(__('assets.actions.new'))
                    ->icon('heroicon-o-plus')
                    ->url(AssetResource::getUrl('create')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('assets.actions.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
