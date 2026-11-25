<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RestrictsTickets;
use App\Http\Requests\Api\V1\StoreTicketReplyRequest;
use App\Http\Requests\Api\V1\UpdateTicketReplyRequest;
use App\Http\Resources\Api\V1\TicketReplyResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Störungen')]
class TicketReplyController extends ApiController
{
    use RestrictsTickets;

    protected static string $ability = 'tickets';

    #[Endpoint('Antworten einer Störung auflisten', 'Antworten und interne Notizen im Verlauf einer Störung, chronologisch.')]
    #[QueryParameter('filter[is_internal_note]', description: 'Nur interne Notizen (true) bzw. nur öffentliche Antworten (false).', type: 'boolean')]
    #[QueryParameter('filter[user_id]', description: 'Benutzer-ID des Verfassers.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: created_at.', type: 'string', default: 'created_at')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->authorizeTicket('view', $ticket);

        $replies = QueryBuilder::for($ticket->replies()->with('author')->when($this->isReporterOnly(), fn (Builder $query) => $query->where('is_internal_note', false)))
            ->allowedFilters(
                AllowedFilter::exact('is_internal_note'),
                AllowedFilter::exact('user_id'),
            )
            ->allowedSorts('created_at')
            ->defaultSort('created_at');

        return TicketReplyResource::collection($this->paginate($replies, $request));
    }

    #[Endpoint('Antwort anzeigen')]
    public function show(TicketReply $reply): TicketReplyResource
    {
        $this->authorizeReply('view', $reply);

        return new TicketReplyResource($reply->load('author'));
    }

    #[Endpoint('Antwort anlegen', 'Der Token-Benutzer wird als Verfasser eingetragen; die erste Antwort eines Bearbeiters stoppt die SLA-Reaktionsuhr.')]
    public function store(StoreTicketReplyRequest $request, Ticket $ticket): TicketReplyResource
    {
        $this->authorizeTicket('view', $ticket);

        $reply = new TicketReply($request->validated());
        $reply->is_internal_note = $reply->is_internal_note && ! $this->isReporterOnly();
        $reply->ticket_id = $ticket->getKey();
        $reply->user_id = $request->user()->getKey();
        $reply->organization_id = $this->organizationId();
        $reply->save();

        return new TicketReplyResource($reply->load('author'));
    }

    #[Endpoint('Antwort ändern')]
    public function update(UpdateTicketReplyRequest $request, TicketReply $reply): TicketReplyResource
    {
        $this->authorizeReply('update', $reply);

        $reply->fill($request->validated())->save();

        return new TicketReplyResource($reply->load('author'));
    }

    #[Endpoint('Antwort löschen')]
    public function destroy(TicketReply $reply): Response
    {
        $this->authorizeReply('delete', $reply);

        $reply->delete();

        return response()->noContent();
    }

    private function authorizeReply(string $ability, TicketReply $reply): void
    {
        $this->authorizeApi($ability, $reply);

        abort_if($reply->ticket === null, 404);
        abort_if($reply->is_internal_note && $this->isReporterOnly(), 403);

        $this->authorizeTicket('view', $reply->ticket);
    }
}
