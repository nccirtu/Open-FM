<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\MeterReadingStatus;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Energy\Resources\MeterResource;
use App\Models\MeterReading;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SuspiciousReadingsWidget extends TableWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 6;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', MeterReading::class) ?? false;
    }

    protected function energyFiltersApplied(): void
    {
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('energy.suspicious.title'))
            ->description(fn (): string => $this->filters()->label())
            ->modelLabel(__('energy.reading.model'))
            ->pluralModelLabel(__('energy.reading.models'))
            ->query(function (): Builder {
                $filters = $this->filters();

                return MeterReading::query()
                    ->with('meter.property')
                    ->where('status', MeterReadingStatus::Suspicious->value)
                    ->whereBetween('read_at', [$filters->from, $filters->to->endOfDay()])
                    ->whereHas('meter', fn (Builder $meters): Builder => $filters->scope($meters));
            })
            ->defaultSort('read_at', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (MeterReading $record): ?string => $record->meter ? MeterResource::getUrl('view', ['record' => $record->meter]) : null)
            ->emptyStateHeading(__('energy.suspicious.empty'))
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('energy.meter.model'))
                    ->description(fn (MeterReading $record): ?string => $record->meter?->property?->property_number),
                TextColumn::make('read_at')
                    ->label(__('energy.fields.read_at'))
                    ->dateTime('d.m.Y H:i'),
                TextColumn::make('flag')
                    ->label(__('energy.fields.status'))
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? __('energy.plausibility.short.'.$state) : null),
            ]);
    }
}
