<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Services\Waste\WasteLookupDefaults;
use Illuminate\Database\Seeder;

class WasteLookupSeeder extends Seeder
{
    public function run(WasteLookupDefaults $defaults): void
    {
        Organization::query()->each(fn (Organization $organization) => $defaults->seed($organization));
    }
}
