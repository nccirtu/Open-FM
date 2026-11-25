<?php

namespace App\Providers;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use LaBoiteACode\FilamentActivityTimeline\ActivityTimeline;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Support\PresentationContext;

class ActivityTimelineServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ActivityTimeline::forModel(Ticket::class)
            ->label(__('tickets.model'))
            ->pluralLabel(__('tickets.models'))
            ->recordTitleUsing(fn (Ticket $ticket): string => $ticket->title)
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('primary')
            ->attributes([
                'title' => AttributePresentation::make(__('tickets.fields.title')),
                'status' => AttributePresentation::make(__('tickets.fields.status'))->enum(TicketStatus::class),
                'priority' => AttributePresentation::make(__('tickets.fields.priority'))->enum(TicketPriority::class),
                'category' => AttributePresentation::make(__('tickets.fields.category'))->enum(TicketCategory::class),
                'type' => AttributePresentation::make(__('tickets.fields.type'))->enum(TicketType::class),
                'property_id' => AttributePresentation::make(__('tickets.fields.property'))
                    ->relationshipUsing(fn ($id): ?string => Property::query()->find($id)?->property_number),
                'asset_id' => AttributePresentation::make(__('tickets.fields.asset'))
                    ->relationshipUsing(fn ($id): ?string => Asset::query()->find($id)?->name),
                'assigned_to' => AttributePresentation::make(__('tickets.fields.assignee'))
                    ->relationshipUsing(fn ($id): ?string => User::query()->find($id)?->name),
                'company_id' => AttributePresentation::make(__('tickets.fields.company'))
                    ->relationshipUsing(fn ($id): ?string => Company::query()->find($id)?->name),
                'due_at' => AttributePresentation::make(__('tickets.fields.due_at'))->dateTime(),
                'resolution_notes' => AttributePresentation::make(__('tickets.fields.resolution_notes')),
            ])
            ->eventSentenceUsing(
                'automation',
                fn ($entry, PresentationContext $context): string => __('tickets.timeline.automation', [
                    'rule' => (string) $context->property('rule'),
                ]),
            );

        ActivityTimeline::event('automation')
            ->label(__('tickets.activities.title'))
            ->icon('heroicon-o-bolt')
            ->color('warning');
    }
}
