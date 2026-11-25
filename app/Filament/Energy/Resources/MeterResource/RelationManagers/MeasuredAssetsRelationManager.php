<?php

namespace App\Filament\Energy\Resources\MeterResource\RelationManagers;

use App\Filament\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Meter;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MeasuredAssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'measuredAssets';

    protected static ?string $inverseRelationship = 'measuringMeters';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('energy.meter.measured_assets');
    }

    public static function shareInput(): TextInput
    {
        return TextInput::make('share')
            ->label(__('energy.fields.share'))
            ->helperText(__('energy.help.share'))
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->default(100)
            ->suffix('%')
            ->required();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([self::shareInput()]);
    }

    public function table(Table $table): Table
    {
        /** @var Meter $meter */
        $meter = $this->getOwnerRecord();
        $year = CarbonImmutable::today()->startOfYear();
        $meterConsumption = app(EnergyReport::class)->consumption([$meter], $year, CarbonImmutable::today());

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('assets.fields.name'))
                    ->searchable()
                    ->weight('medium')
                    ->url(fn (Asset $record): string => AssetResource::getUrl('view', ['record' => $record], panel: 'fm')),
                TextColumn::make('assetType.name')
                    ->label(__('assets.fields.type'))
                    ->placeholder('—'),
                TextColumn::make('share')
                    ->label(__('energy.fields.share'))
                    ->suffix(' %')
                    ->numeric(decimalPlaces: 0),
                TextColumn::make('allocated')
                    ->label(__('energy.fields.consumption_year', ['year' => $year->year]))
                    ->state(fn (Asset $record): string => number_format($meterConsumption * (float) $record->pivot->share / 100, 0, ',', '.').' '.$meter->evaluationUnit())
                    ->alignEnd(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('energy.actions.attach_asset'))
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('assets.property_id', $meter->property_id))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        self::shareInput(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label(__('energy.actions.edit_share')),
                DetachAction::make(),
            ]);
    }
}
