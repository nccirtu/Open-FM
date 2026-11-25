<?php

namespace App\Models;

use App\Enums\CompanyRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class Company extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithMediaFiles;

    protected $fillable = ['name', 'role', 'email', 'phone', 'website', 'vat_id', 'tax_number', 'iban', 'bic', 'notes'];

    protected $attributes = [
        'role' => 'supplier',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['role' => CompanyRole::class];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CompanyAddress::class)->orderByDesc('is_primary');
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CompanyAddress::class)->ofMany(['is_primary' => 'max', 'id' => 'min']);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class)->orderByDesc('is_primary');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(CompanyContact::class)->ofMany(['is_primary' => 'max', 'id' => 'min']);
    }

    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->whereIn('role', [CompanyRole::Supplier->value, CompanyRole::Both->value]);
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->whereIn('role', [CompanyRole::Client->value, CompanyRole::Both->value]);
    }
}
