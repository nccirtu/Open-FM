<?php

namespace App\Filament\Energy\Resources\MeterResource\RelationManagers;

use App\Enums\MeterReadingStatus;
use App\Filament\Energy\Actions\RecordReadingAction;
use App\Filament\Energy\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('energy.reading.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->readings()->where('status', MeterReadingStatus::Suspicious->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return MeterReadingResource::readingsTable($table, withMeter: false)
            ->headerActions([
                RecordReadingAction::make('recordReading', fn (): Model => $this->getOwnerRecord()),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->using(fn (MeterReading $record) => app(MeterReadingRecorder::class)->delete($record)),
            ]);
    }
}
