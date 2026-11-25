<?php

namespace App\Flow;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\MaintenanceOrder;
use App\Models\Project;
use App\Models\Property;
use App\Models\Task;
use App\Models\Ticket;

class PlaceholderCatalog
{
    /**
     * @return array<class-string, array<string, array{0: string, 1: string}>>
     */
    protected static function map(): array
    {
        return [
            Ticket::class => [
                'model.ticket_number' => ['tickets.fields.ticket_number', 'Störungs-Nr.'],
                'model.title' => ['tickets.fields.title', 'Titel'],
                'model.status' => ['tickets.fields.status', 'Status'],
                'model.priority' => ['tickets.fields.priority', 'Priorität'],
                'model.category' => ['tickets.fields.category', 'Kategorie'],
                'model.description' => ['tickets.fields.description', 'Beschreibung'],
                'model.due_at' => ['tickets.fields.due_at', 'Fällig am'],
                'model.property.property_number' => ['property.fields.property_number', 'Objektnummer'],
                'model.property.city' => ['property.fields.city', 'Stadt'],
                'model.asset.name' => ['tickets.fields.asset', 'Asset'],
                'model.structureNode.name' => ['property.structure.location', 'Standort'],
                'model.assignee.name' => ['tickets.fields.assignee', 'Zugewiesen an'],
                'model.reporter.name' => ['tickets.fields.reporter', 'Gemeldet von'],
            ],
            Asset::class => [
                'model.name' => ['assets.fields.name', 'Name'],
                'model.asset_number' => ['assets.fields.asset_number', 'Asset-Nr.'],
                'model.manufacturer' => ['assets.fields.manufacturer', 'Hersteller'],
                'model.serial_number' => ['assets.fields.serial_number', 'Seriennummer'],
                'model.condition' => ['assets.fields.condition', 'Zustand'],
                'model.service_status' => ['assets.fields.service_status', 'Betriebsstatus'],
                'model.warranty_until' => ['assets.fields.warranty_until', 'Garantie bis'],
                'model.assetType.name' => ['maintenance.templates.asset_type', 'Asset-Typ'],
                'model.property.property_number' => ['property.fields.property_number', 'Objektnummer'],
                'model.structureNode.name' => ['assets.fields.location', 'Standort'],
            ],
            Property::class => [
                'model.property_number' => ['property.fields.property_number', 'Objektnummer'],
                'model.street' => ['property.fields.street', 'Straße'],
                'model.zip_code' => ['property.fields.zip_code', 'PLZ'],
                'model.city' => ['property.fields.city', 'Stadt'],
                'model.gross_area' => ['property.fields.gross_area', 'Bruttofläche'],
            ],
            Task::class => [
                'model.title' => ['projects.tasks.title', 'Titel'],
                'model.status' => ['projects.tasks.status', 'Status'],
                'model.priority' => ['projects.tasks.priority', 'Priorität'],
                'model.start_date' => ['projects.tasks.start_date', 'Start am'],
                'model.due_date' => ['projects.tasks.due_date', 'Fällig am'],
                'model.project.name' => ['projects.tasks.project', 'Projekt'],
                'model.assignee.name' => ['projects.tasks.assignee', 'Zugewiesen an'],
            ],
            Project::class => [
                'model.name' => ['projects.fields.name', 'Name'],
                'model.code' => ['projects.fields.code', 'Projekt-Nr.'],
                'model.status' => ['projects.fields.status', 'Status'],
                'model.health' => ['projects.fields.health', 'Ampel'],
                'model.start_date' => ['projects.fields.start_date', 'Start'],
                'model.end_date' => ['projects.fields.end_date', 'Ende'],
                'model.budget' => ['projects.fields.budget', 'Budget'],
                'model.manager.name' => ['projects.fields.manager', 'Projektleitung'],
                'model.property.property_number' => ['property.fields.property_number', 'Objektnummer'],
            ],
            Contract::class => [
                'model.name' => ['contracts.fields.name', 'Bezeichnung'],
                'model.contract_number' => ['contracts.fields.contract_number', 'Vertragsnummer'],
                'model.partner_name' => ['contracts.fields.partner_name', 'Vertragspartner'],
                'model.type' => ['contracts.fields.type', 'Art'],
                'model.status' => ['contracts.fields.status', 'Status'],
                'model.end_date' => ['contracts.fields.end_date', 'Ende'],
                'model.notice_period_days' => ['contracts.fields.notice_period_days', 'Kündigungsfrist (Tage)'],
            ],
            MaintenanceOrder::class => [
                'model.due_at' => ['maintenance.fields.due_at', 'Fällig am'],
                'model.status' => ['tickets.fields.status', 'Status'],
                'model.notes' => ['maintenance.fields.notes', 'Notizen'],
                'model.plan.name' => ['maintenance.plans.model', 'Wartungsplan'],
                'model.asset.name' => ['maintenance.fields.asset', 'Asset'],
                'model.asset.property.property_number' => ['property.fields.property_number', 'Objektnummer'],
            ],
        ];
    }

    /**
     * @return array<class-string, string>
     */
    public static function modelLabels(): array
    {
        return [
            Ticket::class => self::label('tickets.model', 'Störung'),
            Asset::class => 'Asset',
            Property::class => self::label('property.model', 'Objekt'),
            Task::class => self::label('projects.tasks.model', 'Aufgabe'),
            Project::class => self::label('projects.model', 'Projekt'),
            Contract::class => self::label('contracts.model', 'Vertrag'),
            MaintenanceOrder::class => self::label('maintenance.orders.model', 'Wartungsauftrag'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function for(string $modelClass): array
    {
        $result = [];

        foreach (self::map()[$modelClass] ?? [] as $path => [$key, $fallback]) {
            $result['{{ '.$path.' }}'] = self::label($key, $fallback);
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $labels = self::modelLabels();
        $merged = [];

        foreach (self::map() as $modelClass => $paths) {
            foreach ($paths as $path => [$key, $fallback]) {
                $placeholder = '{{ '.$path.' }}';
                $entry = $labels[$modelClass].' · '.self::label($key, $fallback);

                $merged[$placeholder] = isset($merged[$placeholder])
                    ? $merged[$placeholder].' — '.$entry
                    : $entry;
            }
        }

        return $merged;
    }

    protected static function label(string $key, string $fallback): string
    {
        return trans()->has($key) ? __($key) : $fallback;
    }
}
