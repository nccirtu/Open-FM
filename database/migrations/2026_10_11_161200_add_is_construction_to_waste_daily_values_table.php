<?php

use App\Services\Waste\WasteAggregator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('waste_daily_values', 'is_construction')) {
            Schema::table('waste_daily_values', function (Blueprint $table): void {
                $table->boolean('is_construction')->default(false)->after('waste_treatment_category_id');
            });
        }

        $aggregator = app(WasteAggregator::class);

        DB::table('waste_collections')
            ->whereNotNull('project_id')
            ->select(['property_id', 'waste_fraction_id', 'collected_at'])
            ->orderBy('collected_at')
            ->get()
            ->unique(fn (object $row): string => $row->property_id.'|'.$row->waste_fraction_id.'|'.substr((string) $row->collected_at, 0, 10))
            ->each(fn (object $row) => $aggregator->refresh($row->property_id, $row->waste_fraction_id, CarbonImmutable::parse($row->collected_at)));
    }

    public function down(): void
    {
        if (Schema::hasColumn('waste_daily_values', 'is_construction')) {
            Schema::table('waste_daily_values', function (Blueprint $table): void {
                $table->dropColumn('is_construction');
            });
        }
    }
};
