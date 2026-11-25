<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'locale',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * @return list<string>
     */
    public static function rolesForPanel(string $panelId): array
    {
        return match ($panelId) {
            'admin' => ['Admin'],
            'fm' => ['Admin', 'FM', 'Dienstleister'],
            'contracts' => ['Admin', 'Verträge'],
            'energy' => ['Admin', 'FM', 'User'],
            'waste' => ['Admin', 'FM', 'User', 'Abfall', 'Dienstleister'],
            'portal' => ['Admin', 'Melder'],
            default => ['Admin', 'User', 'PM'],
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin' && $this->isSuperAdmin()) {
            return true;
        }

        return $this->hasRoleInAnyOrganization(self::rolesForPanel($panel->getId()));
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    /**
     * @return list<int>|null
     */
    public function assignedPropertyIds(): ?array
    {
        $ids = $this->properties()->pluck('properties.id')->all();

        return $ids === [] ? null : $ids;
    }

    /**
     * @return Collection<int, Organization>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->organizations
            ->filter(fn (Organization $organization): bool => $this->canUsePanelInOrganization($panel, $organization))
            ->values();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $this->organizations()->whereKey($tenant)->exists()) {
            return false;
        }

        $panel = Filament::getCurrentPanel();

        return $panel === null || ! $panel->hasTenancy() || $this->canUsePanelInOrganization($panel, $tenant);
    }

    public function canUsePanelInOrganization(Panel $panel, Model $organization): bool
    {
        return $this->hasRoleInOrganization(self::rolesForPanel($panel->getId()), $organization);
    }

    /**
     * @param  string|list<string>  $roleNames
     */
    public function hasRoleInOrganization(string|array $roleNames, Model|string $organization): bool
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles');

        return Role::query()
            ->whereIn('name', (array) $roleNames)
            ->where(config('permission.column_names.team_foreign_key'), $organization instanceof Model ? $organization->getKey() : $organization)
            ->whereIn('id', DB::table($modelHasRolesTable)
                ->where('model_type', $this->getMorphClass())
                ->where('model_id', $this->getKey())
                ->select(config('permission.column_names.role_pivot_key') ?? 'role_id'))
            ->exists();
    }

    /**
     * @param  string|list<string>  $roleNames
     */
    public function hasRoleInAnyOrganization(string|array $roleNames): bool
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles');

        return Role::query()
            ->whereIn('name', (array) $roleNames)
            ->whereIn('id', DB::table($modelHasRolesTable)
                ->where('model_type', $this->getMorphClass())
                ->where('model_id', $this->getKey())
                ->select(config('permission.column_names.role_pivot_key') ?? 'role_id'))
            ->exists();
    }

    /**
     * @return list<string>|null
     */
    public function administeredOrganizationIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $modelHasRolesTable = config('permission.table_names.model_has_roles');

        return Role::query()
            ->where('name', 'Admin')
            ->whereIn('id', DB::table($modelHasRolesTable)
                ->where('model_type', $this->getMorphClass())
                ->where('model_id', $this->getKey())
                ->select(config('permission.column_names.role_pivot_key') ?? 'role_id'))
            ->pluck(config('permission.column_names.team_foreign_key'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function administersAnyOrganization(): bool
    {
        return $this->administeredOrganizationIds() !== [];
    }

    public function administersOrganization(Organization|string|null $organization): bool
    {
        if ($organization === null) {
            return false;
        }

        $ids = $this->administeredOrganizationIds();

        return $ids === null || in_array($organization instanceof Organization ? $organization->getKey() : $organization, $ids, true);
    }

    public function isManageableBy(self $actor): bool
    {
        if ($actor->isSuperAdmin() || $this->is($actor)) {
            return true;
        }

        if ($this->isSuperAdmin()) {
            return false;
        }

        $administered = $actor->administeredOrganizationIds() ?? [];

        $memberOf = $this->organizations()->pluck('organizations.id')->all();

        return $memberOf !== [] && array_diff($memberOf, $administered) === [];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function restrictedToCompanyId(): ?string
    {
        if ($this->company_id === null) {
            return null;
        }

        if ($this->hasRoleInAnyOrganization(['Admin', 'FM'])) {
            return null;
        }

        return $this->hasRoleInAnyOrganization('Dienstleister') ? $this->company_id : null;
    }
}
