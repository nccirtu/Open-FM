<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'app:super-admin
        {email : E-Mail des Benutzers}
        {--revoke : Super-Admin-Recht entziehen}';

    protected $description = 'Macht einen Benutzer zum Super-Admin der Installation (z. B. für die Plugin-Verwaltung) oder entzieht das Recht';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("Kein Benutzer mit der E-Mail '{$this->argument('email')}' gefunden.");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');

        $user->forceFill(['is_super_admin' => $grant])->save();

        $this->info($grant
            ? "'{$user->email}' ist jetzt Super-Admin der Installation."
            : "'{$user->email}' ist kein Super-Admin mehr.");

        return self::SUCCESS;
    }
}
