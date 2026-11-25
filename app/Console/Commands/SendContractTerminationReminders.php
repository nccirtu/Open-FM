<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SendContractTerminationReminders extends Command
{
    protected $signature = 'contracts:send-reminders';

    protected $description = 'Erinnert an bevorstehende Kündigungsstichtage aktiver Verträge (Vertragsende minus Kündigungsfrist)';

    public function handle(): int
    {
        $sent = 0;

        Contract::query()
            ->active()
            ->whereNull('reminded_at')
            ->whereNotNull('end_date')
            ->get()
            ->filter(fn (Contract $contract): bool => $contract->needsTerminationReminder())
            ->each(function (Contract $contract) use (&$sent): void {
                $recipients = $this->recipientsFor($contract);

                $deadline = $contract->noticeDeadline();

                foreach ($recipients as $recipient) {
                    Notification::make()
                        ->title(__('contracts.reminder.title', ['name' => $contract->name]))
                        ->body(__('contracts.reminder.body', [
                            'partner' => $contract->partner_name,
                            'deadline' => $deadline?->format('d.m.Y'),
                            'end' => $contract->end_date?->format('d.m.Y'),
                        ]))
                        ->warning()
                        ->sendToDatabase($recipient);
                }

                $contract->forceFill(['reminded_at' => now()])->saveQuietly();
                $sent++;
            });

        $this->info("{$sent} Vertrags-Erinnerungen verschickt.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(Contract $contract): Collection
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');

        $userIds = DB::table($modelHasRolesTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->where("{$rolesTable}.organization_id", $contract->organization_id)
            ->whereIn("{$rolesTable}.name", ['Admin', 'Verträge'])
            ->where("{$modelHasRolesTable}.model_type", (new User)->getMorphClass())
            ->pluck("{$modelHasRolesTable}.model_id");

        return User::query()->whereIn('id', $userIds)->get();
    }
}
