<?php

namespace App\Flow\Actions;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Flow\PlaceholderCatalog;
use App\Models\Asset;
use App\Models\Property;
use App\Models\Ticket;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Packstub\Flow\Nodes\Action;
use Packstub\Flow\Nodes\Concerns\InterpolatesPlaceholders;

class CreateTicketAction extends Action
{
    use InterpolatesPlaceholders;

    public function getName(): string
    {
        return __('app.flow_nodes.create_ticket.name');
    }

    public function getDescription(): string
    {
        return __('app.flow_nodes.create_ticket.description');
    }

    public function getIcon(): ?string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public function getPlaceholders(): array
    {
        return PlaceholderCatalog::all();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('app.flow_nodes.create_ticket.title'))
                ->placeholder('{{ model.name }}: …')
                ->required(),
            Textarea::make('description')
                ->label(__('app.flow_nodes.create_ticket.body'))
                ->rows(3),
            Select::make('priority')
                ->label(__('app.flow_nodes.create_ticket.priority'))
                ->options(TicketPriority::class)
                ->default(TicketPriority::P3->value)
                ->required()
                ->native(false),
            Select::make('category')
                ->label(__('app.flow_nodes.create_ticket.category'))
                ->options(TicketCategory::class)
                ->default(TicketCategory::Other->value)
                ->required()
                ->native(false),
        ];
    }

    public function handle(array $config, array $payload): void
    {
        $model = $payload['model'] ?? null;

        [$propertyId, $assetId, $organizationId] = match (true) {
            $model instanceof Asset => [$model->property_id, $model->getKey(), $model->organization_id],
            $model instanceof Property => [$model->getKey(), null, $model->organization_id],
            $model instanceof Ticket => [$model->property_id, $model->asset_id, $model->organization_id],
            default => [$model->property_id ?? null, null, $model->organization_id ?? null],
        };

        if ($propertyId === null) {
            return;
        }

        $ticket = new Ticket([
            'property_id' => $propertyId,
            'asset_id' => $assetId,
            'type' => TicketType::Fault,
            'status' => TicketStatus::Reported,
            'priority' => TicketPriority::tryFrom((string) ($config['priority'] ?? '')) ?? TicketPriority::P3,
            'category' => TicketCategory::tryFrom((string) ($config['category'] ?? '')) ?? TicketCategory::Other,
            'title' => $this->interpolate((string) ($config['title'] ?? ''), $payload),
            'description' => filled($config['description'] ?? null) ? $this->interpolate((string) $config['description'], $payload) : null,
        ]);

        $ticket->organization_id = $organizationId;
        $ticket->save();
    }
}
