<?php

namespace App\Flow\Actions;

use App\Enums\MaintenanceOrderStatus;
use App\Flow\PlaceholderCatalog;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Packstub\Flow\Nodes\Action;
use Packstub\Flow\Nodes\Concerns\InterpolatesPlaceholders;

class CreateMaintenanceOrderAction extends Action
{
    use InterpolatesPlaceholders;

    public function getName(): string
    {
        return __('app.flow_nodes.create_maintenance_order.name');
    }

    public function getDescription(): string
    {
        return __('app.flow_nodes.create_maintenance_order.description');
    }

    public function getIcon(): ?string
    {
        return 'heroicon-o-wrench';
    }

    public function getPlaceholders(): array
    {
        return PlaceholderCatalog::all();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('due_in_days')
                ->label(__('app.flow_nodes.create_maintenance_order.due_in_days'))
                ->numeric()
                ->minValue(0)
                ->default(7)
                ->required(),
            Textarea::make('notes')
                ->label(__('app.flow_nodes.create_maintenance_order.notes'))
                ->rows(3),
        ];
    }

    public function handle(array $config, array $payload): void
    {
        $model = $payload['model'] ?? null;

        $asset = $model instanceof Asset ? $model : ($model->asset ?? null);

        if (! $asset instanceof Asset) {
            return;
        }

        $plan = $asset->maintenancePlans()->where('is_active', true)->orderBy('next_due_at')->first();

        if ($plan === null) {
            return;
        }

        $order = new MaintenanceOrder([
            'maintenance_plan_id' => $plan->getKey(),
            'asset_id' => $asset->getKey(),
            'due_at' => now()->addDays((int) ($config['due_in_days'] ?? 7)),
            'status' => MaintenanceOrderStatus::Open,
            'notes' => filled($config['notes'] ?? null) ? $this->interpolate((string) $config['notes'], $payload) : null,
        ]);

        $order->organization_id = $asset->organization_id;
        $order->save();
    }
}
