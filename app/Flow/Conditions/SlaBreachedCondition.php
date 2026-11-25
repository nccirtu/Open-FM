<?php

namespace App\Flow\Conditions;

use App\Models\Ticket;
use Packstub\Flow\Nodes\Condition;

class SlaBreachedCondition extends Condition
{
    public function getName(): string
    {
        return __('app.flow_nodes.sla_breached.name');
    }

    public function getDescription(): string
    {
        return __('app.flow_nodes.sla_breached.description');
    }

    public function getIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    public function getFormSchema(): array
    {
        return [];
    }

    public function evaluate(array $config, array $payload): bool
    {
        $model = $payload['model'] ?? null;

        return $model instanceof Ticket && $model->isSlaBreached();
    }
}
