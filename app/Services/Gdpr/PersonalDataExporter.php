<?php

namespace App\Services\Gdpr;

use App\Models\CostEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Support\Plugins\Plugins;
use Cafm\FormBuilder\Models\FormSubmission;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type DataGroup array{label: string, records: array<int, array<string, mixed>>}
 */
class PersonalDataExporter
{
    /**
     * @var array<int, array{label: string, model: class-string<Model>, column: string, display: array<int, string>, plugin?: string}>
     */
    private const SOURCES = [
        ['label' => 'Gemeldete Störungen', 'model' => Ticket::class, 'column' => 'reported_by', 'display' => ['id', 'title', 'status', 'created_at']],
        ['label' => 'Zugewiesene Störungen', 'model' => Ticket::class, 'column' => 'assigned_to', 'display' => ['id', 'title', 'status', 'created_at']],
        ['label' => 'Antworten auf Störungen', 'model' => TicketReply::class, 'column' => 'user_id', 'display' => ['id', 'ticket_id', 'created_at']],
        ['label' => 'Zugewiesene Aufgaben', 'model' => Task::class, 'column' => 'assignee_id', 'display' => ['id', 'title', 'status', 'due_date']],
        ['label' => 'Geleitete Projekte', 'model' => Project::class, 'column' => 'manager_id', 'display' => ['id', 'name', 'status']],
        ['label' => 'Erstellte Projekte', 'model' => Project::class, 'column' => 'created_by', 'display' => ['id', 'name', 'status']],
        ['label' => 'Kostenbuchungen', 'model' => CostEntry::class, 'column' => 'created_by', 'display' => ['id', 'amount', 'booked_on', 'description']],
        ['label' => 'Formular-Eingaben', 'model' => FormSubmission::class, 'column' => 'submitted_by', 'display' => ['id', 'form_id', 'submitted_at'], 'plugin' => 'form-builder'],
    ];

    /**
     * @return array<int, DataGroup>
     */
    public function export(User $user): array
    {
        $groups = [];

        foreach (self::SOURCES as $source) {
            if (isset($source['plugin']) && ! Plugins::installed($source['plugin'])) {
                continue;
            }

            $records = $source['model']::query()
                ->withoutGlobalScopes()
                ->where($source['column'], $user->getKey())
                ->get($source['display'])
                ->map(fn (Model $record): array => $record->only($source['display']))
                ->all();

            if ($records !== []) {
                $groups[] = ['label' => $source['label'], 'records' => $records];
            }
        }

        return $groups;
    }
}
