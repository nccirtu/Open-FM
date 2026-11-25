<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tickets', 'ticket_number')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->unsignedInteger('ticket_number')->nullable()->after('organization_id');
            });
        }

        $numbers = [];

        $tickets = DB::table('tickets')
            ->whereNull('ticket_number')
            ->whereNotNull('organization_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'organization_id']);

        foreach ($tickets as $ticket) {
            $numbers[$ticket->organization_id] ??= (int) DB::table('tickets')
                ->where('organization_id', $ticket->organization_id)
                ->max('ticket_number');

            DB::table('tickets')
                ->where('id', $ticket->id)
                ->update(['ticket_number' => ++$numbers[$ticket->organization_id]]);
        }

        if (! Schema::hasIndex('tickets', ['organization_id', 'ticket_number'], 'unique')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->unique(['organization_id', 'ticket_number']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('tickets', ['organization_id', 'ticket_number'], 'unique')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->dropUnique(['organization_id', 'ticket_number']);
            });
        }

        if (Schema::hasColumn('tickets', 'ticket_number')) {
            Schema::table('tickets', function (Blueprint $table): void {
                $table->dropColumn('ticket_number');
            });
        }
    }
};
