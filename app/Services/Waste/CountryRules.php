<?php

namespace App\Services\Waste;

use App\Models\WasteSettings;

final class CountryRules
{
    /**
     * @return array<int, array{key: string, label: string, value: string, applies: bool, default: bool, basis: string, url: string, note: string}>
     */
    public function rows(WasteSettings $settings): array
    {
        $rules = $settings->rules ?? [];
        $has = fn (string $key): bool => array_key_exists($key, $rules);

        $rows = [];

        [$deadline, $deadlineKey] = match (true) {
            $settings->rule('register_deadline_months') !== null => [trans_choice('waste.settings.units.months', (int) $rules['register_deadline_months'], ['count' => $rules['register_deadline_months']]), 'register_deadline_months'],
            $settings->rule('register_deadline_working_days') !== null => [__('waste.settings.units.working_days', ['count' => $rules['register_deadline_working_days']]), 'register_deadline_working_days'],
            $settings->rule('register_deadline_days', 10) !== null => [__('waste.settings.units.calendar_days', ['count' => $settings->rule('register_deadline_days', 10)]), 'register_deadline_days'],
            default => [null, 'register_deadline_days'],
        };
        $rows[] = $this->row($settings, 'register_deadline', $deadline, $deadlineKey, ! $has('register_deadline_days') && ! $has('register_deadline_months') && ! $has('register_deadline_working_days'));

        $retention = $settings->rule('retention_years', 3);
        $rows[] = $this->row($settings, 'retention_years', $retention !== null ? trans_choice('waste.settings.units.years', (int) $retention, ['count' => $retention]) : null, 'retention_years', ! $has('retention_years'));

        $threshold = $settings->rule('hazardous_threshold_t', 2);
        $rows[] = $this->row($settings, 'hazardous_threshold_t', $threshold !== null ? __('waste.settings.units.tonnes_per_year', ['value' => $this->number((float) $threshold)]) : null, 'hazardous_threshold_t', ! $has('hazardous_threshold_t'));

        $quota = $settings->rule('separation_quota_pct', 90);
        $rows[] = $this->row($settings, 'separation_quota_pct', $quota !== null ? $this->number((float) $quota).' %' : null, 'separation_quota_pct', ! $has('separation_quota_pct'));

        $due = $settings->rule('quota_check_due', '03-31');
        $rows[] = $this->row($settings, 'quota_check_due', $due !== null ? __('waste.settings.units.following_year', ['date' => implode('.', array_reverse(explode('-', (string) $due))).'.']) : null, 'quota_check_due', ! $has('quota_check_due'));

        [$construction, $constructionKey] = match (true) {
            $settings->rule('construction_small_quantity_t') !== null => [$this->number((float) $rules['construction_small_quantity_t']).' t', 'construction_small_quantity_t'],
            $settings->rule('construction_small_quantity_m3', 10) !== null => [$this->number((float) $settings->rule('construction_small_quantity_m3', 10)).' m³', 'construction_small_quantity_m3'],
            default => [null, 'construction_small_quantity_m3'],
        };
        $rows[] = $this->row($settings, 'construction_small_quantity', $construction, $constructionKey, ! $has('construction_small_quantity_m3') && ! $has('construction_small_quantity_t'));

        $recovery = $settings->rule('construction_recovery_pct', 70);
        $rows[] = $this->row($settings, 'construction_recovery_pct', $recovery !== null ? $this->number((float) $recovery).' %' : null, 'construction_recovery_pct', ! $has('construction_recovery_pct'));

        $asbestos = $settings->rule('asbestos_ban_year', 1993);
        $rows[] = $this->row($settings, 'asbestos_ban_year', $asbestos !== null ? (string) $asbestos : null, 'asbestos_ban_year', ! $has('asbestos_ban_year'));

        $rows[] = $this->row($settings, 'code_system', __('waste.settings.code_systems.'.match ($settings->country) {
            'at' => 'at',
            'ch' => 'ch',
            default => 'eu',
        }), 'code_system', false);

        return $rows;
    }

    /**
     * @return array{key: string, label: string, value: string, applies: bool, default: bool, basis: string, url: string, note: string}
     */
    private function row(WasteSettings $settings, string $key, ?string $value, string $sourceKey, bool $isDefault): array
    {
        $source = $settings->ruleSource($sourceKey) ?? [];

        return [
            'key' => $key,
            'label' => __('waste.settings.rules.'.$key),
            'value' => $value ?? __('waste.settings.not_applicable'),
            'applies' => $value !== null,
            'default' => $isDefault,
            'basis' => (string) ($source['basis'] ?? ''),
            'url' => (string) ($source['url'] ?? ''),
            'note' => (string) ($source['note'] ?? ''),
        ];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }
}
