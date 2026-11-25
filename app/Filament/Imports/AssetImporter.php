<?php

namespace App\Filament\Imports;

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Filament\Imports\Concerns\ImportsForOrganization;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Support\ImportValue;
use App\Support\Plugins\CustomFieldsBridge;
use BackedEnum;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AssetImporter extends Importer
{
    use ImportsForOrganization;

    protected static ?string $model = Asset::class;

    private const LOOKUP_COLUMNS = ['property', 'location', 'asset_type', 'parent_asset', 'maintenance_partner', 'inspection_company'];

    /**
     * @var array<string, mixed>
     */
    private array $customFieldValues = [];

    public static function getColumns(): array
    {
        $lookup = fn (): null => null;

        return [
            ImportColumn::make('asset_number')
                ->label(__('assets.fields.asset_number'))
                ->helperText(__('assets.import.help.asset_number'))
                ->integer()
                ->rules(['nullable', 'integer', 'min:1'])
                ->fillRecordUsing($lookup)
                ->guess(['asset-nr', 'asset nr', 'assetnummer', 'anlagennummer', 'anlagen-nr', 'nr', 'nr.']),
            ImportColumn::make('name')
                ->label(__('assets.fields.name'))
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:255'])
                ->ignoreBlankState()
                ->guess(['bezeichnung', 'anlage', 'asset', 'anlagenbezeichnung'])
                ->example('Feuerlöscher EG Flur'),
            ImportColumn::make('property')
                ->label(__('property.model'))
                ->helperText(__('assets.import.help.property'))
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing($lookup)
                ->guess(['objekt', 'objektnummer', 'objekt-nr', 'liegenschaft', 'property_number'])
                ->example('1001'),
            ImportColumn::make('location')
                ->label(__('assets.fields.location'))
                ->helperText(__('assets.import.help.location'))
                ->rules(['nullable', 'max:1000'])
                ->fillRecordUsing($lookup)
                ->guess(['standort', 'raum', 'ort', 'location'])
                ->example('Gebäude A › EG › Flur'),
            ImportColumn::make('asset_type')
                ->label(__('assets.fields.type'))
                ->requiredMappingForNewRecordsOnly()
                ->rules(['required', 'max:255'])
                ->fillRecordUsing($lookup)
                ->guess(['typ', 'anlagentyp', 'asset-typ', 'type', 'asset_type'])
                ->example('Feuerlöscher'),
            ImportColumn::make('parent_asset')
                ->label(__('assets.fields.parent_asset'))
                ->helperText(__('assets.import.help.parent_asset'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($lookup)
                ->guess(['übergeordnete anlage', 'parent']),
            ImportColumn::make('manufacturer')
                ->label(__('assets.fields.manufacturer'))
                ->rules(['nullable', 'max:255'])
                ->ignoreBlankState()
                ->guess(['hersteller', 'fabrikat'])
                ->example('Minimax'),
            ImportColumn::make('serial_number')
                ->label(__('assets.fields.serial_number'))
                ->rules(['nullable', 'max:255'])
                ->ignoreBlankState()
                ->guess(['seriennummer', 'seriennr', 'serial']),
            ImportColumn::make('maintenance_partner')
                ->label(__('assets.fields.maintenance_partner'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($lookup)
                ->guess(['wartungspartner', 'wartungsfirma', 'dienstleister', 'company']),
            ImportColumn::make('inspection_company')
                ->label(__('assets.fields.inspection_company'))
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing($lookup)
                ->guess(['prüffirma', 'prüfer', 'sachverständiger']),
            ImportColumn::make('condition')
                ->label(__('assets.fields.condition'))
                ->castStateUsing(fn (mixed $state): mixed => self::enumValue(AssetCondition::class, $state))
                ->rules(['nullable', Rule::enum(AssetCondition::class)])
                ->ignoreBlankState()
                ->guess(['zustand']),
            ImportColumn::make('service_status')
                ->label(__('assets.fields.service_status'))
                ->castStateUsing(fn (mixed $state): mixed => self::enumValue(AssetServiceStatus::class, $state))
                ->rules(['nullable', Rule::enum(AssetServiceStatus::class)])
                ->ignoreBlankState()
                ->guess(['betriebsstatus', 'status']),
            self::dateColumn('installed_at', __('assets.fields.installed_at'), ['installiert', 'inbetriebnahme', 'baujahr']),
            self::dateColumn('warranty_until', __('assets.fields.warranty_until'), ['garantie', 'gewährleistung bis']),
            self::dateColumn('last_maintenance_at', __('assets.fields.last_maintenance'), ['letzte wartung', 'letzte prüfung']),
            self::dateColumn('next_maintenance_at', __('assets.fields.next_maintenance'), ['nächste wartung', 'nächste prüfung']),
            self::dateColumn('decommissioned_at', __('assets.fields.decommissioned_at'), ['stillgelegt', 'außerbetriebnahme']),
            ImportColumn::make('purchase_price')
                ->label(__('assets.fields.purchase_price'))
                ->castStateUsing(fn (mixed $state): mixed => self::numberOrRaw($state))
                ->rules(['nullable', 'numeric', 'min:0'])
                ->ignoreBlankState()
                ->guess(['kaufpreis', 'anschaffungskosten', 'preis']),
            ImportColumn::make('ifc_guid')
                ->label(__('property.bim.ifc_guid'))
                ->rules(['nullable', 'max:64'])
                ->ignoreBlankState()
                ->guess(['ifc-guid', 'ifc guid', 'globalid', 'guid']),
        ];
    }

    /**
     * @param  array<int, string>  $assetTypeIds
     */
    public static function templateCsv(array $assetTypeIds = []): string
    {
        $columns = collect(static::getColumns())
            ->reject(fn (ImportColumn $column): bool => $column->getName() === 'asset_number')
            ->values();
        $types = AssetType::query()->whereKey($assetTypeIds)->orderBy('name')->get();

        $customHeaders = [];

        foreach ($types as $type) {
            $customHeaders = [...$customHeaders, ...CustomFieldsBridge::importHeaders('asset', new Asset(['asset_type_id' => $type->getKey()]))];
        }

        $headers = [...$columns->map(fn (ImportColumn $column): string => $column->getLabel())->all(), ...array_values(array_unique($customHeaders))];
        $propertyNumber = (string) Property::query()->orderBy('property_number')->value('property_number');
        $rows = [];

        foreach ($types as $type) {
            $rows[] = $columns->map(fn (ImportColumn $column): string => match ($column->getName()) {
                'name' => $type->name.' 1',
                'property' => $propertyNumber,
                'asset_type' => $type->name,
                default => '',
            })->pad(count($headers), '')->all();
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\u{FEFF}");

        foreach ([$headers, ...$rows] as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('updateExisting')
                ->label(__('assets.import.options.update_existing')),
            Checkbox::make('createMissing')
                ->label(__('assets.import.options.create_missing')),
        ];
    }

    protected function beforeRow(): void
    {
        $this->customFieldValues = [];
    }

    public function resolveRecord(): ?Asset
    {
        $organizationId = $this->organization()->getKey();
        $assetNumber = $this->data['asset_number'] ?? null;

        if (($this->options['updateExisting'] ?? false) && filled($assetNumber)) {
            $existing = Asset::query()
                ->where('organization_id', $organizationId)
                ->where('asset_number', (int) $assetNumber)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $asset = new Asset;
        $asset->organization_id = $organizationId;

        return $asset;
    }

    protected function beforeSave(): void
    {
        $this->resolveLookups();

        $customFields = CustomFieldsBridge::parseImportRow('asset', $this->record, $this->originalData, $this->mappedHeaders());

        if ($customFields['errors'] !== []) {
            throw new RowImportFailedException(implode(' ', $customFields['errors']));
        }

        $this->customFieldValues = $customFields['values'];
    }

    protected function afterSave(): void
    {
        CustomFieldsBridge::storeImportedValues($this->record, $this->customFieldValues);
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('assets.import.completed_title');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = trans_choice('assets.import.completed_body', $import->successful_rows, ['count' => number_format($import->successful_rows, 0, ',', '.')]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.trans_choice('assets.import.failed_rows', $failedRowsCount, ['count' => number_format($failedRowsCount, 0, ',', '.')]);
        }

        return $body;
    }

    private function resolveLookups(): void
    {
        $asset = $this->record;

        if (filled($value = $this->lookupValue('property'))) {
            $asset->property_id = $this->findProperty($value)->getKey();
        }

        if ($this->isMapped('location') && filled($value = $this->lookupValue('location'))) {
            $asset->structure_node_id = $this->findLocation($asset->property_id, $value)->getKey();
        }

        if (filled($value = $this->lookupValue('asset_type'))) {
            $asset->asset_type_id = $this->findAssetType($value)->getKey();
        }

        if (filled($value = $this->lookupValue('parent_asset'))) {
            $asset->parent_asset_id = $this->findParentAsset($asset, $value)->getKey();
        }

        if (filled($value = $this->lookupValue('maintenance_partner'))) {
            $asset->company_id = $this->findCompany($value)->getKey();
        }

        if (filled($value = $this->lookupValue('inspection_company'))) {
            $asset->inspection_company_id = $this->findCompany($value)->getKey();
        }
    }

    private function findProperty(string $value): Property
    {
        $property = Property::query()
            ->where('organization_id', $this->organization()->getKey())
            ->where(fn (Builder $query): Builder => $query->where('property_number', $value)->when(Str::isUuid($value), fn (Builder $query): Builder => $query->orWhere('id', $value)))
            ->first();

        return $property ?? throw new RowImportFailedException(__('assets.import.errors.property_not_found', ['value' => $value]));
    }

    private function findLocation(?string $propertyId, string $value): StructureNode
    {
        if ($propertyId === null) {
            throw new RowImportFailedException(__('assets.import.errors.location_without_property', ['value' => $value]));
        }

        $nodes = StructureNode::query()->where('property_id', $propertyId)->get();

        if (Str::isUuid($value) && ($node = $nodes->firstWhere('id', $value)) !== null) {
            return $node;
        }

        $segments = array_values(array_filter(array_map(
            fn (string $segment): string => Str::lower(trim($segment)),
            preg_split('/\s*(?:›|»|>|\/|\|)\s*/u', $value) ?: [],
        ), fn (string $segment): bool => $segment !== ''));

        $leaf = $segments[array_key_last($segments) ?? 0] ?? '';
        $candidates = $nodes->filter(fn (StructureNode $node): bool => Str::lower($node->name) === $leaf);

        if (count($segments) > 1) {
            $candidates = $candidates->filter(fn (StructureNode $node): bool => $this->pathMatches($node, $nodes, $segments));
        }

        return match ($candidates->count()) {
            1 => $candidates->first(),
            0 => throw new RowImportFailedException(__('assets.import.errors.location_not_found', ['value' => $value])),
            default => throw new RowImportFailedException(__('assets.import.errors.location_ambiguous', ['value' => $value])),
        };
    }

    /**
     * @param  Collection<int, StructureNode>  $nodes
     * @param  array<int, string>  $segments
     */
    private function pathMatches(StructureNode $node, Collection $nodes, array $segments): bool
    {
        $path = [];
        $current = $node;

        while ($current !== null) {
            array_unshift($path, Str::lower($current->name));
            $current = $current->parent_id !== null ? $nodes->firstWhere('id', $current->parent_id) : null;
        }

        return array_slice($path, -count($segments)) === $segments;
    }

    private function findAssetType(string $value): AssetType
    {
        $name = trim(preg_replace('/^\d[\d.\-]*\s+/u', '', $value) ?? $value);

        $type = AssetType::query()->whereRaw('LOWER(name) = ?', [Str::lower($value)])->first()
            ?? AssetType::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();

        if ($type !== null) {
            return $type;
        }

        if ($this->options['createMissing'] ?? false) {
            return AssetType::query()->create(['name' => $name]);
        }

        throw new RowImportFailedException(__('assets.import.errors.asset_type_not_found', ['value' => $value]));
    }

    private function findParentAsset(Asset $asset, string $value): Asset
    {
        $query = Asset::query()->where('organization_id', $this->organization()->getKey());

        $parent = ctype_digit($value)
            ? (clone $query)->where('asset_number', (int) $value)->first()
            : (clone $query)->where('property_id', $asset->property_id)->where('name', $value)->first();

        if ($parent === null || ($asset->exists && $parent->is($asset))) {
            throw new RowImportFailedException(__('assets.import.errors.parent_not_found', ['value' => $value]));
        }

        return $parent;
    }

    private function findCompany(string $value): Company
    {
        $organizationId = $this->organization()->getKey();

        $company = Company::query()
            ->where('organization_id', $organizationId)
            ->whereRaw('LOWER(name) = ?', [Str::lower($value)])
            ->first();

        if ($company !== null) {
            return $company;
        }

        if ($this->options['createMissing'] ?? false) {
            $company = new Company(['name' => $value]);
            $company->organization_id = $organizationId;
            $company->save();

            return $company;
        }

        throw new RowImportFailedException(__('assets.import.errors.company_not_found', ['value' => $value]));
    }

    private function isMapped(string $column): bool
    {
        return filled($this->columnMap[$column] ?? null);
    }

    private function lookupValue(string $column): ?string
    {
        if (! in_array($column, self::LOOKUP_COLUMNS, true) || ! $this->isMapped($column)) {
            return null;
        }

        $value = trim((string) ($this->data[$column] ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function mappedHeaders(): array
    {
        return array_values(array_filter($this->columnMap, fn (mixed $header): bool => filled($header)));
    }

    /**
     * @param  array<int, string>  $guesses
     */
    private static function dateColumn(string $name, string $label, array $guesses): ImportColumn
    {
        return ImportColumn::make($name)
            ->label($label)
            ->castStateUsing(fn (mixed $state): mixed => self::dateOrRaw($state))
            ->rules(['nullable', 'date'])
            ->ignoreBlankState()
            ->guess($guesses);
    }

    private static function dateOrRaw(mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        try {
            return ImportValue::date((string) $state)->toDateString();
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

    /**
     * @param  class-string<BackedEnum&HasLabel>  $enum
     */
    private static function enumValue(string $enum, mixed $state): mixed
    {
        if (blank($state)) {
            return null;
        }

        $needle = Str::lower(trim((string) $state));

        foreach ($enum::cases() as $case) {
            $labels = [Str::lower($case->value), Str::lower((string) $case->getLabel())];

            foreach (array_keys(Organization::LOCALES) as $locale) {
                $labels[] = Str::lower((string) trans(self::enumTranslationKey($case), locale: $locale));
            }

            if (in_array($needle, $labels, true)) {
                return $case->value;
            }
        }

        return $state;
    }

    private static function enumTranslationKey(BackedEnum $case): string
    {
        return match (true) {
            $case instanceof AssetCondition => 'assets.condition.'.$case->value,
            $case instanceof AssetServiceStatus => 'assets.service_status.'.$case->value,
            default => '',
        };
    }
}
