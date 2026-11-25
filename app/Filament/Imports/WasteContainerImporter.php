<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ImportsForOrganization;
use App\Models\Company;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteContainer;
use App\Models\WasteContainerType;
use App\Models\WasteFraction;
use App\Support\ImportValue;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use InvalidArgumentException;

class WasteContainerImporter extends Importer
{
    use ImportsForOrganization;

    protected static ?string $model = WasteContainer::class;

    public static function getColumns(): array
    {
        $ignore = fn (): null => null;

        return [
            ImportColumn::make('number')
                ->label(__('waste.fields.number'))
                ->requiredMapping()
                ->rules(['required', 'max:50'])
                ->guess(['nummer', 'behälter', 'behälternummer', 'number'])
                ->example('B-0101'),
            ImportColumn::make('property')
                ->label(__('property.model'))
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['objekt', 'objektnummer', 'property'])
                ->example('DEMO-01'),
            ImportColumn::make('location')
                ->label(__('waste.fields.location'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['standort', 'sammelstelle', 'location'])
                ->example('Müllplatz Nord'),
            ImportColumn::make('fraction')
                ->label(__('waste.models.fraction.singular'))
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['fraktion', 'abfallschlüssel', 'avv', 'fraction'])
                ->example('20 01 01'),
            ImportColumn::make('type')
                ->label(__('waste.models.container_type.singular'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['typ', 'behältertyp', 'type'])
                ->example(__('waste.defaults.container_types.bin')),
            ImportColumn::make('volume_l')
                ->label(__('waste.fields.volume'))
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): mixed => self::numberOrRaw($state))
                ->rules(['required', 'numeric', 'min:1'])
                ->guess(['volumen', 'liter', 'volume'])
                ->example('1100'),
            ImportColumn::make('company')
                ->label(__('waste.fields.company'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($ignore)
                ->guess(['entsorger', 'firma', 'company'])
                ->example('Muster Entsorgung GmbH'),
        ];
    }

    public function resolveRecord(): ?WasteContainer
    {
        return WasteContainer::query()
            ->where('organization_id', $this->organization()?->getKey())
            ->where('number', trim((string) $this->data['number']))
            ->first() ?? new WasteContainer;
    }

    protected function beforeSave(): void
    {
        $organizationId = $this->organization()?->getKey();
        $property = Property::query()->where('organization_id', $this->organizationId())->where('property_number', trim((string) $this->data['property']))->first()
            ?? throw new RowImportFailedException(__('waste.import.errors.property_not_found', ['value' => $this->data['property']]));
        $fractionValue = trim((string) $this->data['fraction']);
        $fraction = WasteFraction::query()->where('organization_id', $this->organizationId())->where('code', $fractionValue)->first()
            ?? WasteFraction::query()->where('organization_id', $this->organizationId())->where('name', $fractionValue)->first()
            ?? throw new RowImportFailedException(__('waste.import.errors.fraction_not_found', ['value' => $fractionValue]));

        $this->record->organization_id ??= $organizationId;
        $this->record->property_id = $property->getKey();
        $this->record->waste_fraction_id = $fraction->getKey();
        $this->record->structure_node_id = filled($this->data['location'] ?? null)
            ? StructureNode::query()->where('property_id', $property->getKey())->where('name', trim((string) $this->data['location']))->value('id')
            : $this->record->structure_node_id;
        $this->record->waste_container_type_id = filled($this->data['type'] ?? null)
            ? WasteContainerType::query()->where('organization_id', $this->organizationId())->where('name', trim((string) $this->data['type']))->value('id')
            : $this->record->waste_container_type_id;
        $this->record->company_id = filled($this->data['company'] ?? null)
            ? Company::query()->where('organization_id', $this->organizationId())->where('name', trim((string) $this->data['company']))->value('id')
            : $this->record->company_id;
        $this->record->is_active ??= true;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = trans_choice('waste.import.containers_completed', $import->successful_rows, ['count' => number_format($import->successful_rows, 0, ',', '.')]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.trans_choice('assets.import.failed_rows', $failedRowsCount, ['count' => number_format($failedRowsCount, 0, ',', '.')]);
        }

        return $body;
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
