<?php

namespace App\Filament\Imports;

use App\Enums\MeterReadingSource;
use App\Filament\Imports\Concerns\ImportsForOrganization;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\Energy\MeterReadingRecorder;
use App\Support\ImportValue;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use InvalidArgumentException;

class MeterReadingImporter extends Importer
{
    use ImportsForOrganization;

    protected static ?string $model = MeterReading::class;

    public static function getColumns(): array
    {
        $ignore = fn (): null => null;

        return [
            ImportColumn::make('meter')
                ->label(__('energy.meter.model'))
                ->helperText(__('energy.import.help.meter'))
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['zähler', 'zählernummer', 'zaehler', 'meter', 'zähler-nr', 'messstelle'])
                ->example('1ESY1160512345'),
            ImportColumn::make('read_at')
                ->label(__('energy.fields.read_at'))
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): mixed => self::dateTimeOrRaw($state))
                ->rules(['required', 'date'])
                ->fillRecordUsing($ignore)
                ->guess(['datum', 'ablesedatum', 'zeitpunkt', 'date'])
                ->example('01.10.2026 08:00'),
            ImportColumn::make('value')
                ->label(__('energy.fields.value'))
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): mixed => self::numberOrRaw($state))
                ->rules(['required', 'numeric', 'min:0'])
                ->fillRecordUsing($ignore)
                ->guess(['stand', 'zählerstand', 'wert', 'value'])
                ->example('184312,4'),
            ImportColumn::make('note')
                ->label(__('energy.fields.note'))
                ->rules(['nullable', 'max:1000'])
                ->fillRecordUsing($ignore)
                ->guess(['bemerkung', 'notiz', 'note']),
        ];
    }

    public function resolveRecord(): ?MeterReading
    {
        return new MeterReading;
    }

    public function saveRecord(): void
    {
        $meter = $this->findMeter(trim((string) $this->data['meter']));

        try {
            $this->record = app(MeterReadingRecorder::class)->record(
                $meter,
                CarbonImmutable::parse($this->data['read_at']),
                (float) $this->data['value'],
                MeterReadingSource::Import,
                ['note' => filled($this->data['note'] ?? null) ? (string) $this->data['note'] : null],
            );
        } catch (InvalidArgumentException $exception) {
            throw new RowImportFailedException($exception->getMessage());
        }
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('energy.import.completed_title');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = trans_choice('energy.import.completed_body', $import->successful_rows, ['count' => number_format($import->successful_rows, 0, ',', '.')]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.trans_choice('assets.import.failed_rows', $failedRowsCount, ['count' => number_format($failedRowsCount, 0, ',', '.')]);
        }

        return $body;
    }

    private function findMeter(string $value): Meter
    {
        $query = Meter::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where('kind', '!=', 'virtual');

        $meter = (clone $query)->where('number', $value)->first();

        if ($meter !== null) {
            return $meter;
        }

        $byName = (clone $query)->where('name', $value)->get();

        return match ($byName->count()) {
            1 => $byName->first(),
            0 => throw new RowImportFailedException(__('energy.import.errors.meter_not_found', ['value' => $value])),
            default => throw new RowImportFailedException(__('energy.import.errors.meter_ambiguous', ['value' => $value])),
        };
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
