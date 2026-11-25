<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function scopeAdministeredByCurrentUser(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $organizationIds = $user->administeredOrganizationIds();

        return $organizationIds === null
            ? $query
            : $query->whereIn($query->getModel()->getTable().'.id', $organizationIds);
    }

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var array<string, string>
     */
    public const LOCALES = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ro' => 'Română',
        'hr' => 'Hrvatski',
    ];

    protected $fillable = [
        'name',
        'slug',
        'default_locale',
        'allowed_email_domains',
    ];

    protected $attributes = [
        'default_locale' => 'de',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_email_domains' => 'array',
        ];
    }

    public function allowsEmail(string $email): bool
    {
        $domains = $this->allowed_email_domains;

        if (blank($domains)) {
            return true;
        }

        $emailDomain = strtolower(str($email)->afterLast('@')->toString());

        return collect($domains)
            ->map(fn (string $domain): string => strtolower(ltrim(trim($domain), '@')))
            ->contains($emailDomain);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
