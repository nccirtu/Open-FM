<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AssignRole extends Command
{
    protected $signature = 'app:assign-role
        {email : E-Mail des Benutzers}
        {role=Admin : Name der Rolle}
        {--organization= : Slug oder ID des Mandanten (ohne Angabe: alle Mandanten des Benutzers bzw. der einzige vorhandene)}
        {--verify-email : E-Mail-Adresse zusätzlich als bestätigt markieren}';

    protected $description = 'Weist einem Benutzer je Mandant eine Rolle zu (ersetzt vorhandene Rollen im Mandanten)';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("Kein Benutzer mit der E-Mail '{$this->argument('email')}' gefunden.");

            return self::FAILURE;
        }

        $organizations = $this->targetOrganizations($user);

        if ($organizations === null) {
            return self::FAILURE;
        }

        $roleName = $this->argument('role');
        $previousTeamId = getPermissionsTeamId();

        try {
            foreach ($organizations as $organization) {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where('organization_id', $organization->getKey())
                    ->first();

                if ($role === null) {
                    $this->error("Rolle '{$roleName}' existiert im Mandanten '{$organization->slug}' nicht. Zuerst 'php artisan db:seed --class=RolesAndPermissionsSeeder' ausführen.");

                    return self::FAILURE;
                }

                $user->organizations()->syncWithoutDetaching([$organization->getKey()]);

                setPermissionsTeamId($organization->getKey());
                $user->unsetRelation('roles');
                $user->syncRoles([$role]);

                $this->info("'{$user->email}' hat im Mandanten '{$organization->slug}' jetzt die Rolle '{$roleName}'.");
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        if ($this->option('verify-email') && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->info("E-Mail-Adresse von '{$user->email}' als bestätigt markiert.");
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Organization>|null
     */
    protected function targetOrganizations(User $user): ?Collection
    {
        $option = $this->option('organization');

        if (filled($option)) {
            $organization = Organization::query()
                ->where('slug', $option)
                ->orWhere('id', $option)
                ->first();

            if ($organization === null) {
                $this->error("Mandant '{$option}' nicht gefunden.");

                return null;
            }

            return collect([$organization]);
        }

        if ($user->organizations()->exists()) {
            return $user->organizations()->get();
        }

        $organizations = Organization::query()->limit(2)->get();

        if ($organizations->count() === 1) {
            return $organizations;
        }

        $this->error($organizations->isEmpty()
            ? "Es gibt noch keinen Mandanten. Zuerst 'php artisan db:seed --class=RolesAndPermissionsSeeder' ausführen."
            : 'Der Benutzer gehört keinem Mandanten an. Mandanten mit --organization=<slug> angeben.');

        return null;
    }
}
