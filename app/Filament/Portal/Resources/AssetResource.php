<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\AssetResource\Pages\ListAssets;
use App\Models\Asset;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?int $navigationSort = 20;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return 'Asset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Assets';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.objects');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $assignedIds = auth()->user()?->assignedPropertyIds();

        if ($assignedIds !== null) {
            $query->whereIn('property_id', $assignedIds);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('assets.fields.name'))
                    ->searchable(),
                TextColumn::make('assetType.name')
                    ->label(__('maintenance.templates.asset_type')),
                TextColumn::make('property.property_number')
                    ->label(__('tickets.fields.property'))
                    ->description(fn (Asset $record): ?string => $record->property?->city),
                TextColumn::make('structureNode.name')
                    ->label(__('property.structure.location'))
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('property_id')
                    ->label(__('tickets.fields.property'))
                    ->relationship('property', 'property_number'),
            ])
            ->recordActions([
                Action::make('report')
                    ->label(__('tickets.portal.report_for_asset'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn (Asset $record): string => TicketResource::getUrl('create', [
                        'property_id' => $record->property_id,
                        'asset_id' => $record->getKey(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
        ];
    }
}
