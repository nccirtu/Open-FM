<?php

namespace App\Filament\Imports;

use App\Enums\WasteCollectionSource;
use App\Filament\Imports\Concerns\ImportsForOrganization;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WasteTreatment;
use App\Services\Waste\WasteCollectionRecorder;
use App\Support\ImportValue;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use InvalidArgumentException;

class WasteCollectionImporter extends Importer
{
    use ImportsForOrganization;

    protected static ?string $model = WasteCollection::class;

    public static function getColumns(): array
    {
        $ignore = fn (): null => null;

        return [
            ImportColumn::make('container')
                ->label(__('waste.models.container.singular'))
                ->rules(['nullable', 'max:50'])
                ->fillRecordUsing($ignore)
                ->guess(['behälter', 'behälternummer', 'container'])
                ->example('B-0101'),
            ImportColumn::make('property')
                ->label(__('property.model'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['objekt', 'objektnummer', 'property'])
                ->example('DEMO-01'),
            ImportColumn::make('fraction')
                ->label(__('waste.models.fraction.singular'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['fraktion', 'abfallschlüssel', 'avv', 'fraction'])
                ->example('20 01 01'),
            ImportColumn::make('collected_at')
                ->label(__('waste.fields.collected_at'))
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): mixed => self::dateTimeOrRaw($state))
                ->rules(['required', 'date'])
                ->fillRecordUsing($ignore)
                ->guess(['datum', 'leerung', 'abholung', 'date'])
                ->example('06.10.2026'),
            ImportColumn::make('mass_kg')
                ->label(__('waste.fields.mass_kg'))
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): mixed => self::numberOrRaw($state))
                ->rules(['required', 'numeric', 'min:0'])
                ->fillRecordUsing($ignore)
                ->guess(['gewicht', 'menge', 'kg', 'mass'])
                ->example('184,5'),
            ImportColumn::make('treatment')
                ->label(__('waste.models.treatment.singular'))
                ->rules(['nullable', 'max:20'])
                ->fillRecordUsing($ignore)
                ->guess(['verfahren', 'r/d', 'treatment'])
                ->example('R3'),
        ];
    }

    public function resolveRecord(): ?WasteCollection
    {
        return new WasteCollection;
    }

    public function saveRecord(): void
    {
        $recorder = app(WasteCollectionRecorder::class);
        $collectedAt = CarbonImmutable::parse($this->data['collected_at']);
        $mass = (float) $this->data['mass_kg'];
        $treatment = filled($this->data['treatment'] ?? null)
            ? WasteTreatment::query()->where('organization_id', $this->organizationId())->where('code', trim((string) $this->data['treatment']))->value('id')
            : null;
        $attributes = $treatment !== null ? ['waste_treatment_id' => $treatment] : [];

        try {
            if (filled($this->data['container'] ?? null)) {
                $container = WasteContainer::query()->where('organization_id', $this->organizationId())->where('number', trim((string) $this->data['container']))->first()
                    ?? throw new RowImportFailedException(__('waste.import.errors.container_not_found', ['value' => $this->data['container']]));

                $this->record = $recorder->recordForContainer($container, $collectedAt, WasteCollectionSource::Import, massKg: $mass, attributes: $attributes);

                return;
            }

            $property = Property::query()->where('organization_id', $this->organizationId())->where('property_number', trim((string) ($this->data['property'] ?? '')))->first()
                ?? throw new RowImportFailedException(__('waste.import.errors.property_not_found', ['value' => $this->data['property'] ?? '']));
            $fractionValue = trim((string) ($this->data['fraction'] ?? ''));
            $fraction = WasteFraction::query()->where('organization_id', $this->organizationId())->where('code', $fractionValue)->first()
                ?? WasteFraction::query()->where('organization_id', $this->organizationId())->where('name', $fractionValue)->first()
                ?? throw new RowImportFailedException(__('waste.import.errors.fraction_not_found', ['value' => $fractionValue]));

            $this->record = $recorder->recordForProperty($property, $fraction, $collectedAt, $mass, WasteCollectionSource::Import, $attributes);
        } catch (InvalidArgumentException $exception) {
            throw new RowImportFailedException($exception->getMessage());
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = trans_choice('waste.import.collections_completed', $import->successful_rows, ['count' => number_format($import->successful_rows, 0, ',', '.')]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.trans_choice('assets.import.failed_rows', $failedRowsCount, ['count' => number_format($failedRowsCount, 0, ',', '.')]);
        }

        return $body;
    }

    private static function dateTimeOrRaw(mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        try {
            return ImportValue::date((string) $state)->format('Y-m-d H:i:s');
        } catch (InvalidArgumentException) {
            return $state;
        }
    }

    private static function numberOrRaw(mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        try {
            return ImportValue::number((string) $state);
        } catch (InvalidArgumentException) {
            return $state;
        }
    }
}
