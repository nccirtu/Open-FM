<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyContact extends Model
{
    use HasUuids;

    protected $fillable = ['company_id', 'first_name', 'last_name', 'position', 'email', 'phone', 'mobile', 'is_primary', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (CompanyContact $contact): void {
            if ($contact->is_primary) {
                static::query()->where('company_id', $contact->company_id)->whereKeyNot($contact->getKey())->update(['is_primary' => false]);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }
}
