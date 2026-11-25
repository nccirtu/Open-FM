<?php

namespace App\Models;

use App\Enums\PluginSource;
use App\Enums\PluginStatus;
use App\Support\Plugins\PluginManager;
use Database\Factories\PluginFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plugin extends Model
{
    /** @use HasFactory<PluginFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = [
        'slug',
        'package',
        'name',
        'version',
        'source',
        'status',
        'is_enabled',
        'license_key',
        'license_status',
        'license_checked_at',
        'last_error',
        'settings',
        'installed_at',
    ];

    protected $hidden = ['license_key'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => PluginSource::class,
            'status' => PluginStatus::class,
            'is_enabled' => 'boolean',
            'license_key' => 'encrypted',
            'license_checked_at' => 'datetime',
            'settings' => 'array',
            'installed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(PluginManager::class)->flush());
        static::deleted(fn () => app(PluginManager::class)->flush());
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class);
    }

    public function isAvailableFor(?Organization $organization): bool
    {
        if ($organization === null || $this->organizations->isEmpty()) {
            return true;
        }

        return $this->organizations->contains($organization);
    }
}
