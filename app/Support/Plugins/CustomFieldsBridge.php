<?php

namespace App\Support\Plugins;

use App\Filament\Integrations\CustomFields\CustomFieldImport;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Support\SectionRegistry;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CustomFieldsBridge
{
    public const PLUGIN = 'custom-fields';

    /**
     * @return array<int, mixed>
     */
    public static function formComponents(string $entity): array
    {
        return Plugins::active(self::PLUGIN) ? CustomFields::formComponents($entity) : [];
    }

    /**
     * @return array<int, mixed>
     */
    public static function infolistComponents(string $entity): array
    {
        return Plugins::active(self::PLUGIN) ? CustomFields::infolistComponents($entity) : [];
    }

    /**
     * @return array<int, mixed>
     */
    public static function tableColumns(string $entity, bool $commonOnly = false): array
    {
        if (! Plugins::active(self::PLUGIN)) {
            return [];
        }

        $columns = CustomFields::tableColumns($entity);

        return $commonOnly ? self::withoutConditionalSections($entity, $columns) : $columns;
    }

    /**
     * @return array<int, mixed>
     */
    public static function exportColumns(string $entity): array
    {
        return Plugins::active(self::PLUGIN) ? CustomFields::exportColumns($entity) : [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $ignoredHeaders
     * @return array{values: array<string, mixed>, errors: array<int, string>}
     */
    public static function parseImportRow(string $entity, Model $record, array $row, array $ignoredHeaders = []): array
    {
        if (! Plugins::active(self::PLUGIN)) {
            return ['values' => [], 'errors' => []];
        }

        return CustomFieldImport::parse($entity, $record, $row, $ignoredHeaders);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function storeImportedValues(Model $record, array $values): void
    {
        if ($values !== [] && Plugins::active(self::PLUGIN)) {
            CustomFieldImport::store($record, $values);
        }
    }

    /**
     * @return array<int, string>
     */
    public static function importHeaders(string $entity, ?Model $record = null): array
    {
        return Plugins::active(self::PLUGIN) ? CustomFieldImport::headers($entity, $record) : [];
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<int, Column>
     */
    private static function withoutConditionalSections(string $entity, array $columns): array
    {
        $prefixes = SectionRegistry::for($entity)
            ->filter(fn (CustomFieldSection $section): bool => filled($section->visible_when))
            ->map(fn (CustomFieldSection $section): string => CustomFields::relationshipName().'.data.'.$section->key.'.')
            ->values()
            ->all();

        if ($prefixes === []) {
            return $columns;
        }

        return array_values(array_filter(
            $columns,
            fn (Column $column): bool => ! Str::startsWith($column->getName(), $prefixes),
        ));
    }
}
