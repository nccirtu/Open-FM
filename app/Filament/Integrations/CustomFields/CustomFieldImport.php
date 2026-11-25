<?php

namespace App\Filament\Integrations\CustomFields;

use App\Support\ImportValue;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Models\CustomFieldValue;
use Cafm\CustomFields\Support\ConditionResolver;
use Cafm\CustomFields\Support\SectionRegistry;
use Cafm\FormBuilder\Enums\FieldType;
use Cafm\FormBuilder\Schema\SchemaRenderer;
use Cafm\FormBuilder\Support\EntityRegistry;
use Cafm\FormBuilder\Support\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class CustomFieldImport
{
    private const NOT_IMPORTABLE = [FieldType::Photos, FieldType::File, FieldType::Signature, FieldType::Repeater];

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $ignoredHeaders
     * @return array{values: array<string, mixed>, errors: array<int, string>}
     */
    public static function parse(string $entity, Model $record, array $row, array $ignoredHeaders = []): array
    {
        $cells = self::normalizedCells($row, $ignoredHeaders);
        $values = [];
        $errors = [];

        if ($cells === []) {
            return ['values' => $values, 'errors' => $errors];
        }

        foreach (self::applicableSections($entity, $record) as $section) {
            foreach (self::fieldsOf($section) as $field) {
                $cell = self::findCell($cells, $field['headers']);

                if ($cell === null || blank($cell['value'])) {
                    continue;
                }

                try {
                    foreach (self::convert($field, trim((string) $cell['value'])) as $key => $value) {
                        $values[$section->key] = self::setPath($values[$section->key] ?? [], [...$field['parents'], $key], $value);
                    }
                } catch (InvalidArgumentException $exception) {
                    $errors[] = $cell['header'].': '.$exception->getMessage();
                }
            }
        }

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function store(Model $record, array $values): void
    {
        if ($values === []) {
            return;
        }

        $relation = $record->{CustomFields::relationshipName()}();
        $existing = $relation->first();

        if ($existing instanceof CustomFieldValue) {
            $existing->update(['data' => array_replace_recursive($existing->data ?? [], $values)]);

            return;
        }

        $relation->create(['data' => $values]);
    }

    /**
     * @return array<int, string>
     */
    public static function headers(string $entity, ?Model $record = null): array
    {
        $sections = $record === null ? SectionRegistry::for($entity) : self::applicableSections($entity, $record);
        $headers = [];

        foreach ($sections as $section) {
            foreach (self::fieldsOf($section) as $field) {
                $headers[] = $field['template_header'];
            }
        }

        return array_values(array_unique($headers));
    }

    /**
     * @return array<int, CustomFieldSection>
     */
    private static function applicableSections(string $entity, Model $record): array
    {
        return SectionRegistry::for($entity)
            ->filter(fn (CustomFieldSection $section): bool => self::passes($section, $record))
            ->values()
            ->all();
    }

    private static function passes(CustomFieldSection $section, Model $record): bool
    {
        try {
            return ConditionResolver::passesForRecord($section->visible_when ?? [], $record, recordOnly: true);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * @return array<int, array{type: FieldType, spec: array<string, mixed>, key: string, parents: array<int, string>, headers: array<int, string>, template_header: string}>
     */
    private static function fieldsOf(CustomFieldSection $section, ?array $specs = null, array $parents = [], array $prefixes = []): array
    {
        $prefixes = $prefixes === [] ? self::labelsOf(['label' => $section->label, 'translations' => $section->translations ?? []], $section->key) : $prefixes;
        $fields = [];

        foreach ($specs ?? $section->fieldSpecs() as $field) {
            $type = FieldType::tryFrom($field['type'] ?? '');
            $spec = $field['data'] ?? [];
            $key = $spec['key'] ?? null;

            if ($type === null || blank($key) || $type->isStatic() || in_array($type, self::NOT_IMPORTABLE, true)) {
                continue;
            }

            $labels = self::labelsOf($spec, $key);

            if ($type === FieldType::Group) {
                $groupPrefixes = [];

                foreach ($prefixes as $prefix) {
                    foreach ($labels as $label) {
                        $groupPrefixes[] = $prefix.': '.$label;
                    }
                }

                array_push($fields, ...self::fieldsOf($section, $spec['fields'] ?? [], [...$parents, $key], $groupPrefixes));

                continue;
            }

            $headers = [...$labels, $key, implode('.', [$section->key, ...$parents, $key])];

            foreach ($prefixes as $prefix) {
                foreach ($labels as $label) {
                    $headers[] = $prefix.': '.$label;
                }
            }

            $fields[] = [
                'type' => $type,
                'spec' => $spec,
                'key' => $key,
                'parents' => $parents,
                'headers' => array_values(array_unique(array_map(ImportValue::normalizeHeader(...), $headers))),
                'template_header' => $prefixes[0].': '.(Translatable::text($spec) ?? $key),
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, string>
     */
    private static function labelsOf(array $spec, string $fallback): array
    {
        $labels = [Translatable::text($spec) ?? $fallback, (string) ($spec['label'] ?? $fallback)];

        foreach ($spec['translations'] ?? [] as $translation) {
            if (filled($translation['label'] ?? null)) {
                $labels[] = (string) $translation['label'];
            }
        }

        return array_values(array_unique(array_filter($labels, fn (string $label): bool => $label !== '')));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $ignoredHeaders
     * @return array<string, array{header: string, value: mixed}>
     */
    private static function normalizedCells(array $row, array $ignoredHeaders): array
    {
        $ignored = array_map(ImportValue::normalizeHeader(...), $ignoredHeaders);
        $cells = [];

        foreach ($row as $header => $value) {
            $normalized = ImportValue::normalizeHeader((string) $header);

            if ($normalized === '' || in_array($normalized, $ignored, true) || isset($cells[$normalized])) {
                continue;
            }

            $cells[$normalized] = ['header' => (string) $header, 'value' => $value];
        }

        return $cells;
    }

    /**
     * @param  array<string, array{header: string, value: mixed}>  $cells
     * @param  array<int, string>  $headers
     * @return array{header: string, value: mixed}|null
     */
    private static function findCell(array $cells, array $headers): ?array
    {
        foreach ($headers as $header) {
            if (isset($cells[$header])) {
                return $cells[$header];
            }
        }

        return null;
    }

    /**
     * @param  array{type: FieldType, spec: array<string, mixed>, key: string}  $field
     * @return array<string, mixed>
     */
    private static function convert(array $field, string $raw): array
    {
        $spec = $field['spec'];
        $key = $field['key'];

        return match ($field['type']) {
            FieldType::Number, FieldType::Rating => [$key => ImportValue::number($raw)],
            FieldType::Toggle => [$key => ImportValue::boolean($raw)],
            FieldType::Date => [$key => ImportValue::date($raw)->toDateString()],
            FieldType::DateTime => [$key => ImportValue::date($raw)->format('Y-m-d H:i:s')],
            FieldType::Select, FieldType::Radio => [$key => self::option($spec, $raw)],
            FieldType::CheckboxList => [$key => array_map(
                fn (string $item): string => (string) self::option($spec, trim($item)),
                array_values(array_filter(preg_split('/[;,|\n]/', $raw) ?: [], fn (string $item): bool => trim($item) !== '')),
            )],
            FieldType::RadioOther => self::optionOrOther($spec, $key, $raw),
            FieldType::Entity => [$key => self::entity((string) ($spec['entity'] ?? ''), $raw)],
            default => [$key => $raw],
        };
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private static function option(array $spec, string $raw): string|int
    {
        if (SchemaRenderer::usesEntitySource($spec)) {
            return self::entity((string) $spec['entity'], $raw);
        }

        $needle = Str::lower($raw);

        foreach ($spec['options'] ?? [] as $option) {
            $option = is_string($option) ? ['label' => $option] : $option;

            if (! is_array($option)) {
                continue;
            }

            $value = (string) ($option['value'] ?? Translatable::text($option, 'label', Translatable::defaultLocale()) ?? '');
            $candidates = [$value, ...self::labelsOf($option, $value)];

            if ($value !== '' && in_array($needle, array_map(Str::lower(...), $candidates), true)) {
                return $value;
            }
        }

        throw new InvalidArgumentException(__('assets.import.errors.invalid_option', ['value' => $raw]));
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    private static function optionOrOther(array $spec, string $key, string $raw): array
    {
        try {
            return [$key => self::option($spec, $raw)];
        } catch (InvalidArgumentException) {
            return [$key => SchemaRenderer::OTHER_VALUE, $key.'_other' => $raw];
        }
    }

    private static function entity(string $entity, string $raw): string|int
    {
        $definition = EntityRegistry::get($entity);
        $query = EntityRegistry::query($entity);

        if ($definition === null || $query === null) {
            throw new InvalidArgumentException(__('assets.import.errors.invalid_option', ['value' => $raw]));
        }

        $model = $query->getModel();
        $record = (clone $query)->where($model->qualifyColumn($definition['title']), $raw)->first()
            ?? (Str::isUuid($raw) || is_numeric($raw) ? (clone $query)->whereKey($raw)->first() : null);

        if ($record === null) {
            throw new InvalidArgumentException(__('assets.import.errors.invalid_option', ['value' => $raw]));
        }

        return $record->getKey();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $path
     * @return array<string, mixed>
     */
    private static function setPath(array $data, array $path, mixed $value): array
    {
        $key = array_shift($path);

        if ($path === []) {
            $data[$key] = $value;

            return $data;
        }

        $data[$key] = self::setPath(is_array($data[$key] ?? null) ? $data[$key] : [], $path, $value);

        return $data;
    }
}
