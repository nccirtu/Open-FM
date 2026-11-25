<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAddress extends Model
{
    use HasUuids;

    protected $fillable = ['company_id', 'type', 'label', 'street', 'zip_code', 'city', 'state', 'country', 'is_primary'];

    protected $attributes = [
        'type' => 'billing',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (CompanyAddress $address): void {
            if ($address->is_primary) {
                static::query()->where('company_id', $address->company_id)->whereKeyNot($address->getKey())->update(['is_primary' => false]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function oneLine(): string
    {
        return implode(', ', array_filter([$this->street, trim(($this->zip_code ?? '').' '.($this->city ?? '')), $this->country]));
    }
}
