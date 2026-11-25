<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\EntityFolders;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class SyncEntityFolders extends Command
{
    protected $signature = 'folders:sync {--organization= : Nur diesen Mandanten (ID) verarbeiten}';

    protected $description = 'Dokumentenverwaltungs-Ordner für Objekte, Räume, Assets, Projekte, Störungen, Wartung, Verträge und Firmen anlegen bzw. abgleichen';

    public function handle(): int
    {
        Filament::setCurrentPanel(Filament::getPanel('fm'));

        $organizations = Organization::query()
            ->when($this->option('organization'), fn ($query, $id) => $query->whereKey($id))
            ->get();

        foreach ($organizations as $organization) {
            Filament::setTenant($organization, isQuiet: true);
            $count = 0;

            foreach (EntityFolders::MODELS as $model) {
                $model::query()->where('organization_id', $organization->getKey())->each(function (Model $entity) use (&$count): void {
                    if (EntityFolders::folderFor($entity, create: false) === null) {
                        EntityFolders::folderFor($entity);
                        $count++;
                    } else {
                        EntityFolders::sync($entity);
                    }
                });
            }

            $this->info("{$organization->name}: {$count} Ordner angelegt.");
        }

        Filament::setTenant(null, isQuiet: true);

        return self::SUCCESS;
    }
}
