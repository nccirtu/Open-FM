<?php

namespace App\Filament\Portal\Widgets;

use App\Filament\Portal\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Support\Portal\TicketStatusHistory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportNewsWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 10;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function getHeading(): ?string
    {
        return __('portal.widgets.news');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('portal.widgets.news'))
            ->records(fn (): Collection => $this->news())
            ->emptyStateHeading(__('portal.widgets.news_empty'))
            ->recordUrl(fn (array $record): string => TicketResource::getUrl('view', ['record' => $record['ticket_id']]))
            ->paginated(false)
            ->columns([
                TextColumn::make('at')
                    ->label(__('portal.widgets.news_when'))
                    ->since(),
                TextColumn::make('ticket')
                    ->label(__('portal.widgets.news_report'))
                    ->limit(32),
                TextColumn::make('event')
                    ->label(__('portal.widgets.news_event'))
                    ->wrap(),
            ]);
    }

    /**
     * @return Collection<string, array{at: mixed, ticket_id: string, ticket: string, event: string}>
     */
    protected function news(): Collection
    {
        $user = auth()->user();

        $replies = TicketReply::query()
            ->where('is_internal_note', false)
            ->whereIn('ticket_id', Ticket::query()->reportedBy($user)->select('id'))
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->getKey()))
            ->with(['ticket', 'author'])
            ->latest()
            ->limit(8)
            ->get();

        $changes = TicketStatusHistory::recent(Ticket::query()->reportedBy($user), 8);
        $changedTickets = Ticket::query()->whereKey($changes->pluck('ticket_id'))->get()->keyBy('id');

        return $replies
            ->map(fn (TicketReply $reply): array => [
                'key' => 'reply-'.$reply->getKey(),
                'at' => $reply->created_at,
                'ticket_id' => (string) $reply->ticket_id,
                'ticket' => $this->label($reply->ticket),
                'event' => __('portal.widgets.news_reply', [
                    'author' => $reply->author?->name ?? __('portal.widgets.news_author_unknown'),
                    'text' => Str::limit(strip_tags($reply->message), 80),
                ]),
            ])
            ->concat($changes->map(fn (array $change): array => [
                'key' => 'status-'.$change['ticket_id'].'-'.$change['at']->getTimestamp().'-'.$change['to']->value,
                'at' => $change['at'],
                'ticket_id' => $change['ticket_id'],
                'ticket' => $this->label($changedTickets->get($change['ticket_id'])),
                'event' => $change['from'] === null
                    ? $change['to']->getLabel()
                    : $change['from']->getLabel().' → '.$change['to']->getLabel(),
            ]))
            ->sortByDesc('at')
            ->take(8)
            ->mapWithKeys(fn (array $row): array => [$row['key'] => $row]);
    }

    protected function label(?Ticket $ticket): string
    {
        if ($ticket === null) {
            return '—';
        }

        return trim((filled($ticket->ticket_number) ? "#{$ticket->ticket_number} " : '').$ticket->title);
    }
}
