<?php

use App\Flow\Actions\CreateMaintenanceOrderAction;
use App\Flow\Actions\CreateTicketAction;
use App\Flow\Conditions\SlaBreachedCondition;
use App\Flow\Triggers\RecordCreated;
use App\Flow\Triggers\RecordDeleted;
use App\Flow\Triggers\RecordUpdated;
use Filament\Http\Middleware\Authenticate;
use Packstub\Flow\Models\Secret;
use Packstub\Flow\Models\Workflow;
use Packstub\Flow\Models\WorkflowRun;
use Packstub\Flow\Models\WorkflowStep;
use Packstub\Flow\Models\WorkflowTrigger;
use Packstub\Flow\Models\WorkflowVersion;
use Packstub\Flow\Models\WorkflowWait;
use Packstub\Flow\Nodes;

return [

    'tables' => [
        'workflows' => 'flow_workflows',
        'triggers' => 'flow_workflow_triggers',
        'runs' => 'flow_workflow_runs',
        'secrets' => 'flow_secrets',
        'steps' => 'flow_workflow_steps',
        'waits' => 'flow_workflow_waits',
        'versions' => 'flow_workflow_versions',
    ],

    'models' => [
        'workflow' => Workflow::class,
        'trigger' => WorkflowTrigger::class,
        'run' => WorkflowRun::class,
        'secret' => Secret::class,
        'step' => WorkflowStep::class,
        'wait' => WorkflowWait::class,
        'version' => WorkflowVersion::class,
    ],

    'tenancy' => [
        'relationship' => env('PACKSTUB_FLOW_TENANT_RELATIONSHIP'),
    ],

    'versions' => [
        'keep' => 50,
    ],

    'queue' => [
        'enabled' => (bool) env('PACKSTUB_FLOW_QUEUE', false),
        'connection' => env('PACKSTUB_FLOW_QUEUE_CONNECTION'),
        'queue' => env('PACKSTUB_FLOW_QUEUE_NAME'),
        'timeout' => (int) env('PACKSTUB_FLOW_QUEUE_TIMEOUT', 300),
    ],

    'max_steps' => 10000,

    'max_records' => 1000,

    'max_nesting' => 5,

    'max_output_bytes' => 16384,

    'http' => [
        'timeout' => (int) env('PACKSTUB_FLOW_HTTP_TIMEOUT', 15),
        'retry_after_ms' => 500,
        'block_private_networks' => (bool) env('PACKSTUB_FLOW_HTTP_BLOCK_PRIVATE', true),
        'allowed_hosts' => [],
    ],

    'register_schedule' => true,

    'schedule_catch_up_minutes' => (int) env('PACKSTUB_FLOW_SCHEDULE_CATCH_UP', 0),

    'schedule_on_one_server' => (bool) env('PACKSTUB_FLOW_SCHEDULE_ONE_SERVER', false),

    'webhooks' => [
        'enabled' => true,
        'prefix' => 'flow/webhooks',
        'middleware' => ['api', 'throttle:60,1'],
        'redacted_headers' => ['authorization', 'proxy-authorization', 'cookie', 'x-api-key', 'x-auth-token', 'x-signature', 'x-hub-signature', 'x-hub-signature-256'],
    ],

    'approvals' => [
        'prefix' => 'flow/approvals',
        'middleware' => ['web', Authenticate::class],
        'link_lifetime_hours' => 72,
    ],

    'models_for_triggers' => [],

    'triggers' => [
        Nodes\Triggers\Manual::class,
        Nodes\Triggers\Schedule::class,
        Nodes\Triggers\Webhook::class,
        RecordCreated::class,
        RecordUpdated::class,
        RecordDeleted::class,
        Nodes\Triggers\UserRegistered::class,
        Nodes\Triggers\EventFired::class,
        Nodes\Triggers\WorkflowCalled::class,
        Nodes\Triggers\DateReached::class,
        Nodes\Triggers\StateTransitioned::class,
        Nodes\Triggers\StatusChanged::class,
    ],

    'actions' => [
        Nodes\Actions\SendEmail::class,
        Nodes\Actions\SendNotification::class,
        Nodes\Actions\SendSlackMessage::class,
        Nodes\Actions\SendDiscordMessage::class,
        Nodes\Actions\SendTeamsMessage::class,
        Nodes\Actions\SendTelegramMessage::class,
        Nodes\Actions\SendSms::class,
        Nodes\Actions\HttpRequest::class,
        Nodes\Actions\UpdateRecord::class,
        Nodes\Actions\CreateRecord::class,
        Nodes\Actions\AssignOwner::class,
        Nodes\Actions\TransitionState::class,
        Nodes\Actions\AddTag::class,
        Nodes\Actions\FindRecords::class,
        Nodes\Actions\ForEachLoop::class,
        Nodes\Actions\Wait::class,
        Nodes\Actions\RequestApproval::class,
        Nodes\Actions\WaitForEvent::class,
        Nodes\Actions\CallWorkflow::class,
        Nodes\Actions\WriteLog::class,
        CreateTicketAction::class,
        CreateMaintenanceOrderAction::class,
    ],

    'conditions' => [
        Nodes\Conditions\RecordAttribute::class,
        Nodes\Conditions\CompareValues::class,
        Nodes\Conditions\MultipleConditions::class,
        Nodes\Conditions\TimeOfDay::class,
        SlaBreachedCondition::class,
    ],

    'navigation' => [
        'group' => null,
        'icon' => 'heroicon-o-bolt',
        'sort' => null,
    ],

    'prune_runs_after_days' => 30,

    'notifications' => [
        'recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('PACKSTUB_FLOW_NOTIFY', ''))))),
    ],

    'gate' => env('PACKSTUB_FLOW_GATE'),

];
