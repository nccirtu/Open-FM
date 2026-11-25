<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, mixed>|null $rules
 * @property array<string, array{basis?: string, url?: string, note?: string}>|null $rule_sources
 * @property array<int, string>|null $evidence_types
 */
class WasteSettings extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $table = 'waste_settings';

    protected $fillable = [
        'country',
        'cutoff_days',
        'cutoff_time',
        'horizon_weeks',
        'default_fill_pct',
        'staging_reminder_time',
        'request_reminder_days',
        'sensor_request_pct',
        'rules',
        'rule_sources',
        'evidence_types',
    ];

    protected $attributes = [
        'cutoff_days' => 1,
        'cutoff_time' => '09:00',
        'horizon_weeks' => 8,
        'default_fill_pct' => 75,
        'staging_reminder_time' => '18:00',
        'request_reminder_days' => 2,
        'sensor_request_pct' => 80,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cutoff_days' => 'integer',
            'horizon_weeks' => 'integer',
            'default_fill_pct' => 'integer',
            'request_reminder_days' => 'integer',
            'sensor_request_pct' => 'integer',
            'rules' => 'array',
            'rule_sources' => 'array',
            'evidence_types' => 'array',
        ];
    }

    public static function forOrganization(?string $organizationId): self
    {
        $organizationId ??= Filament::getTenant()?->getKey();

        $settings = $organizationId !== null
            ? static::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->first()
            : null;

        if ($settings === null) {
            $settings = new self;
            $settings->organization_id = $organizationId;
        }

        return $settings;
    }

    public function rule(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->rules ?? []) ? $this->rules[$key] : $default;
    }

    /**
     * @var array<string, string>
     */
    public const DEFAULT_EVIDENCE = [
        'site_plan' => 'always',
        'photos' => 'always',
        'practice_records' => 'always',
        'acceptor_declaration' => 'always',
        'pretreatment_confirmation' => 'mixed',
        'exception_reason' => 'mixed',
    ];

    /**
     * @return array<int, string>
     */
    public function evidenceTypes(): array
    {
        return array_keys($this->evidenceRequirements());
    }

    /**
     * @return array<string, array{when: string, basis: string, url: string}>
     */
    public function evidenceRequirements(): array
    {
        if (blank($this->evidence_types)) {
            return array_map(fn (string $when): array => ['when' => $when, 'basis' => '', 'url' => ''], self::DEFAULT_EVIDENCE);
        }

        $requirements = [];

        foreach ($this->evidence_types as $entry) {
            $key = is_array($entry) ? (string) ($entry['key'] ?? '') : (string) $entry;

            if ($key === '') {
                continue;
            }

            $requirements[$key] = [
                'when' => is_array($entry) ? (string) ($entry['when'] ?? 'always') : (self::DEFAULT_EVIDENCE[$key] ?? 'always'),
                'basis' => is_array($entry) ? (string) ($entry['basis'] ?? '') : '',
                'url' => is_array($entry) ? (string) ($entry['url'] ?? '') : '',
            ];
        }

        return $requirements;
    }

    /**
     * @return array{basis?: string, url?: string, note?: string, confidence?: string}|null
     */
    public function ruleSource(string $key): ?array
    {
        return $this->rule_sources[$key] ?? null;
    }
}
